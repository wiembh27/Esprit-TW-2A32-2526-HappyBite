<?php
include(__DIR__ . '/../config.php');
include(__DIR__ . '/../Models/Category.php');
// require_once __DIR__ . '/../Models/Category.php';

class CategoryController
{

    public function addCategory(Category $category)
    {
        $sql = "INSERT INTO category (title, description) VALUES (:title, :description)";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'title' => $category->getTitle(),
                'description' => $category->getDescription()
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function listCategories()
    {
        $sql = "SELECT * FROM category";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute();
            $categoriesData = $query->fetchAll();

            $categories = [];
            foreach ($categoriesData as $row) {
                $category = new Category($row['title'], $row['description']);
                $category->setId($row['id']);
                $categories[] = $category;
            }
            return $categories;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }

    public function deleteCategory($id)
    {
        $sql = "DELETE FROM category WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            return true;
        } catch (Exception $e) {
            // Vous pouvez gérer l'erreur selon vos besoins (log, affichage, etc.)
            return false;
        }
    }
}