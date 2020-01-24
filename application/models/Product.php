<?php

namespace application\models;

use application\exceptions\SearchException;

class Product  extends ActiveRecordEntity
{
    public function getAll()
    {
        $products = array();
        foreach ($this->db->connection->query("SELECT * FROM " . $this->getTableName())->fetchAll() as $product) {
            $brandObject = new Brand();
            $brandTitle = $brandObject->getTitle($product['brand_id']);
            $productAttributesObject = new ProductAttribute();
            $productAttributes = $productAttributesObject->getAttributesIdByProductId($product['id']);
            $attributeObject = new Attribute();
            $attributes = array();
            foreach ($productAttributes as $productAttribute) {
                $attributes[] = $attributeObject->getTitle($productAttribute->attributeId);
            }

            $productObject = new self();
            $productObject->id = $product['id'];
            $productObject->title = $product['title'];
            $productObject->description = $product['description'];
            $productObject->sale_price = $product['sale_price'];
            $productObject->code = $product['code'];
            $productObject->brand_id = $product['brand_id'];
            $productObject->is_sale = $product['is_sale'];
            $productObject->image = $product['image'];
            $productObject->brand = $brandTitle;
            $productObject->brand_id = $product['brand_id'];
            $productObject->price = $product['price'];
            $productObject->quantity = $product['quantity'];
            $productObject->attributes = $attributes;
            $products[] = $productObject;
        }

        return $products;
    }

    public function getById($ids)
    {
        $query = "SELECT * FROM " . $this->getTableName() . " WHERE id = ";
        for ($i = 0; $i < count($ids); $i++) {
            if (!$i) $query .= "$ids[$i]";
            else $query .= " OR id = $ids[$i]";
        }

        $productsData = $this->db->connection->query($query)->fetchAll();
        $products = [];
        foreach ($productsData as $product) {
            $brandObject = new Brand();
            $brandTitle = $brandObject->getTitle($product['brand_id']);
            $productAttributesObject = new ProductAttribute();
            $productAttributes = $productAttributesObject->getAttributesIdByProductId($product['id']);
            $attributeObject = new Attribute();
            $attributes = array();
            foreach ($productAttributes as $productAttribute) {
                $attributes[] = $attributeObject->getTitle($productAttribute->attributeId);
            }

            $productAlikeObject = new ProductAlike();
            $productsAlike = [];
            foreach ($productAlikeObject->getByProductId($product['id']) as $productAlike) {
                $productAttribute = new ProductAttribute();
                $productAttributes = $productAttribute->getAttributesIdByProductId($productAlike->id);
                foreach ($productAttributes as $productAttribute) {
                    $attributeObject = new Attribute();
                    $attribute = $attributeObject->getTitle($productAttribute->attributeId);
                    $productsAlike[] = [
                        'id' => $productAlike->id,
                        'title' => $attribute->title
                    ];
                }
            }

            $productObject = new self();
            $productObject->id = $product['id'];
            $productObject->title = $product['title'];
            $productObject->description = $product['description'];
            $productObject->sale_price = $product['sale_price'];
            $productObject->code = $product['code'];
            $productObject->brand_id = $product['brand_id'];
            $productObject->is_sale = $product['is_sale'];
            $productObject->image = $product['image'];
            $productObject->brand = $brandTitle;
            $productObject->brand_id = $product['brand_id'];
            $productObject->price = $product['price'];
            $productObject->quantity = $product['quantity'];
            $productObject->attributes = $attributes;
            $productObject->alikeOnes = $productsAlike;

            if (count($productsData) === 1) return $productObject;
            $products[] = $productObject;
        }

        return $products;
    }

    public function getByBrand($brandId)
    {
        $products = array();
        $productsData = $this->db->connection->query("SELECT id FROM products WHERE brand_id = $brandId")->fetchAll();
        if ($productsData) {
            foreach ($productsData as $productData) {
                $products[] = $this->getById($productData['id']);
            }

            return $products;
        } else throw new SearchException('Products not found');
    }

    public function getByCategory($categoryId)
    {
        $products = array();
        $productsData = $this->db->connection->query("SELECT id FROM products WHERE category_id = $categoryId")->fetchAll();
        if ($productsData) {
            foreach ($productsData as $productData) {
                $products[] = $this->getById($productData['id']);
            }

            return $products;
        } else throw new SearchException('No products in this category');
    }

    public function getBySubCategory($subCategoryId)
    {
        $products = array();
        $productsData = $this->db->connection->query("SELECT id FROM products WHERE subcategory_id = $subCategoryId")->fetchAll();
        if ($productsData) {
            foreach ($productsData as $productData) {
                $products[] = $this->getById($productData['id']);
            }

            return $products;
        } else throw new SearchException('No products in this subcategory');
    }

    public function getByTitle($title)
    {
        $products = array();
        $productsData = $this->db->connection->query("SELECT id FROM products WHERE title like '%$title%'")->fetchAll();
        if ($productsData) {
            foreach ($productsData as $productData) {
                $products[] = $this->getById($productData['id']);
            }

            return $products;
        } else throw new SearchException("No results for $title");
    }

//    public function getByAttribute($attributeIds)
//    {
//        $query = "SELECT product_id FROM products_attributes WHERE attribute_id = ";
//        $attributesQuantity = count($attributeIds);
//        for ($i = 0; $i < $attributesQuantity; $i++) {
//             if (!$i) $query .= $attributeIds[$i];
//             else $query .= " OR attribute_id = $attributeIds[$i]";
//        }
//
//        return $this->db->connection->query($query)->fetchAll();
//    }

    public function getByAttributeAndSubCategory($attributesIds, $subCategoryId)
    {
        $products = array();
//        $query = "SELECT * FROM products WHERE subcategory_id = $subCategoryId";
        $query = "select products.* FROM products left join products_attributes on 
                    products.id = products_attributes.product_id where products.subcategory_id = $subCategoryId AND (";
        $attributesIdsQuantity = count($attributesIds);
        for ($i = 0; $i < $attributesIdsQuantity; $i++) {
            if (!$i) $query .= "products_attributes.attribute_id = {$attributesIds[$i]}";
            else $query .= " OR products_attributes.attribute_id = {$attributesIds[$i]}";
            if ($i === $attributesIdsQuantity - 1) $query .= ')';
        }

//        return $query;
        $stmt = $this->db->connection->query($query);
        if ($stmt) $productsData = $stmt->fetchAll();
        else return '';
        $products = [];
        foreach ($productsData as $product) {
            $brandObject = new Brand();
            $brandTitle = $brandObject->getTitle($product['brand_id']);
            $productAttributesObject = new ProductAttribute();
            $productAttributes = $productAttributesObject->getAttributesIdByProductId($product['id']);
            $attributeObject = new Attribute();
            $attributes = array();
            foreach ($productAttributes as $productAttribute) {
                $attributes[] = $attributeObject->getTitle($productAttribute->attributeId);
            }

            $productAlikeObject = new ProductAlike();
            $productsAlike = [];
            foreach ($productAlikeObject->getByProductId($product['id']) as $productAlike) {
                $productAttribute = new ProductAttribute();
                $productAttributes = $productAttribute->getAttributesIdByProductId($productAlike->id);
                foreach ($productAttributes as $productAttribute) {
                    $attributeObject = new Attribute();
                    $attribute = $attributeObject->getTitle($productAttribute->attributeId);
                    $productsAlike[] = [
                        'id' => $productAlike->id,
                        'title' => $attribute->title
                    ];
                }
            }

            $productObject = new self();
            $productObject->id = $product['id'];
            $productObject->title = $product['title'];
            $productObject->description = $product['description'];
            $productObject->sale_price = $product['sale_price'];
            $productObject->code = $product['code'];
            $productObject->brand_id = $product['brand_id'];
            $productObject->is_sale = $product['is_sale'];
            $productObject->image = $product['image'];
            $productObject->brand = $brandTitle;
            $productObject->brand_id = $product['brand_id'];
            $productObject->price = $product['price'];
            $productObject->quantity = $product['quantity'];
            $productObject->attributes = $attributes;
            $productObject->alikeOnes = $productsAlike;

            if (count($productsData) === 1) return $productObject;
            $products[] = $productObject;
        }

        return $products;
    }

    public function getTableName()
    {
        return 'products';
    }
}