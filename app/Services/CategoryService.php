<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Aspect;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;

class CategoryService
{
    private function getBaseUrl(): string
    {
        // Get the active credential's environment
        $credential = \App\Models\Credential::where('is_active', true)->first();

        if (!$credential) {
            throw new \Exception('No active eBay credential found');
        }

        // Return appropriate base URL based on environment
        return $credential->environment === 'sandbox'
            ? 'https://api.sandbox.ebay.com/'
            : 'https://api.ebay.com/';
    }

    public function getCategorySuggestions(string $title): array
    {
        // Check cache table first
        $cache = \App\Models\CategorySearchResult::where('title', $title)->first();
        if ($cache) {
            return $cache->suggestions;
        }

        // If not cached, call eBay API
        $response = \Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getEbayToken(),
            'Content-Type' => 'application/json',
        ])->get($this->getBaseUrl() . 'commerce/taxonomy/v1/category_tree/0/get_category_suggestions', [
            'q' => $title,
        ]);

        $suggestions = [];
        if ($response->successful()) {
            $data = $response->json();
            $suggestions = $data['categorySuggestions'] ?? [];
            // Cache the result
            \App\Models\CategorySearchResult::create([
                'title' => $title,
                'suggestions' => $suggestions,
            ]);
        }

        return $suggestions;
    }

    public function getCategorySpecifics(string $categoryId): array
    {
        try {
            $baseUrl = $this->getBaseUrl();
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getEbayToken(),
                'Content-Type' => 'application/json'
            ])->get($baseUrl . 'commerce/taxonomy/v1/category_tree/0/get_item_aspects_for_category', [
                'category_id' => $categoryId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aspects = [];

                if (isset($data['aspects'])) {
                    foreach ($data['aspects'] as $aspect) {
                        $aspectConstraint = $aspect['aspectConstraint'] ?? [];

                        $aspects[] = [
                            'name' => $aspect['localizedAspectName'] ?? '',
                            'required' => $aspectConstraint['aspectRequired'] ?? false,
                            'data_type' => $aspectConstraint['aspectDataType'] ?? 'STRING',
                            'mode' => $aspectConstraint['aspectMode'] ?? 'FREE_TEXT',
                            'usage' => $aspectConstraint['aspectUsage'] ?? 'RECOMMENDED',
                            'values' => $this->extractAspectValues($aspect['aspectValues'] ?? [])
                        ];
                    }
                }

                return $aspects;
            } else {
                return [];
            }
        } catch (\Exception $e) {
            \Log::error('eBay Category Specifics API Error: ' . $e->getMessage());
            return [];
        }
    }

    private function extractAspectValues(array $aspectValues): array
    {
        $values = [];
        foreach ($aspectValues as $value) {
            $values[] = [
                'value' => $value['localizedValue'] ?? '',
                'constraint' => $value['constraint'] ?? null
            ];
        }
        return $values;
    }

    public function getAspectsForCategory(Category $category): array
    {
        // Check if aspects exist in DB
        $aspects = $category->aspects;
        if ($aspects->isNotEmpty()) {
            return $aspects->toArray();
        }

        // Get category specifics from eBay API
        $specifics = $this->getCategorySpecifics($category->ebay_category_id);
        
        $result = [];
        foreach ($specifics as $aspect) {
            $aspectModel = Aspect::updateOrCreate(
                [ 'category_id' => $category->id, 'name' => $aspect['name'] ],
                [ 
                    'values' => $aspect['values'],
                    'required' => $aspect['required'],
                    'data_type' => $aspect['data_type'],
                    'mode' => $aspect['mode'],
                    'usage' => $aspect['usage']
                ]
            );
            $result[] = $aspectModel->toArray();
        }
        return $result;
    }

    public function assignCategoryToProduct($product, $ebayCategoryId, $categoryName)
    {
        // Fetch specifics from eBay API
        $specifics = $this->getCategorySpecifics($ebayCategoryId);

        // Upsert category with specifics
        $category = \App\Models\Category::updateOrCreate(
            ['ebay_category_id' => $ebayCategoryId],
            [
                'name' => $categoryName,
                'specifics' => $specifics,
            ]
        );

        // Assign to product
        $product->category_id = $category->id;
        $product->save();

        return $category;
    }

    /**
     * @throws GuzzleException
     */
    private function getEbayToken(): string
    {
        $apiService = new \App\Services\ApiService;
        return $apiService->getAppAccessToken();
    }
}
