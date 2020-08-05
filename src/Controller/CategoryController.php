<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\EditCategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategoryController extends AbstractController
{
    /**
     * @Route("/categorie", name="category")
     */
    public function index()
    {
        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
        ]);
    }

    /**
     * @Route("admin/categories", name="manage_categories")
     */
    public function manageCategories(CategoryRepository $repos, PaginatorInterface $paginator, Request $request)
    {
        $paginatedCategories = $paginator->paginate(
            $repos->findAll(), // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );

        return $this->render('admin/categories.html.twig', [
            'categories' => $paginatedCategories,
        ]);
    }

    /**
     * @Route("/admin/categories/supprimer/{id}", name="remove_category")
     */
    public function removeCategory($id, CategoryRepository $repos, EntityManagerInterface $manager)
    {
        $category = $repos->find($id);

        $manager->remove($category);
        $manager->flush();

        return $this->redirectToRoute('manage_categories');
    }

    /**
     * @Route("/admin/categories/modifier/{id}", name="edit_category")
     */
    public function editCategory($id, CategoryRepository $repos, EntityManagerInterface $manager, Request $request)
    {
        $category = $repos->find($id);

        $form = $this->createForm(EditCategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $category->setCreatedAt(new \DateTime());

            $manager->persist($category);
            $manager->flush();

            return $this->redirectToRoute('manage_categories');
        }

        return $this->render('admin/edit_category.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    /**
     * @Route("/admin/categories/ajouter", name="create_category")
     */
    public function createCategory(EntityManagerInterface $manager, Request $request)
    {
        $category = new Category();

        $form = $this->createForm(EditCategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $category->setCreatedAt(new \DateTime());

            $manager->persist($category);
            $manager->flush();

            return $this->redirectToRoute('manage_categories');
        }

        return $this->render('admin/edit_category.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }
}
