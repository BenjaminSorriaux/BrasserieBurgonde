<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\AddType;
use App\Entity\Command;
use App\Entity\Comment;
use App\Entity\Product;
use App\Form\CommentType;
use App\Form\EditProductType;
use App\Repository\NewsRepository;
use App\Repository\OrderRepository;
use App\Repository\StatusRepository;
use App\Repository\CommandRepository;
use App\Repository\CommentRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    /**
     * @Route("/produits", name="products")
     */
    public function index(ProductRepository $productRepos, CategoryRepository $categoryRepos, PaginatorInterface $paginator, Request $request)
    {
        $paginatedProducts = $paginator->paginate(
            $productRepos->findAll(), // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );
        $categories = $categoryRepos->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $paginatedProducts,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/", name="home")
     */
    public function home(ProductRepository $productRepos, NewsRepository $newsRepos, CategoryRepository $categoryRepos)
    {
        $products = $productRepos->findAll();
        $news = $newsRepos->findByDate(3);
        $categories = $categoryRepos->findAll();

        $mobileProducts = $productRepos->findByDate(3);

        return $this->render('product/home.html.twig', [
            'products' => $products,
            'mobile_products' => $mobileProducts,
            'news' => $news,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/produit/{id}", name="product_details")
     */
    public function productDetails($id, EntityManagerInterface $manager, ProductRepository $repos, CommentRepository $commentRepos, CommandRepository $commandRepos, OrderRepository $orderRepos, StatusRepository $statusRepos, Request $request)
    {
        try {
            $product = $repos->find($id);
            $comments = $commentRepos->findByProductId($id);
            $category = $product->getCategory();
            $productsLinked = $repos->findByCategoryExceptOne($category->getId(), $id);

            // Add to cart form
            $form = $this->createForm(AddType::class);
            $form->handleRequest($request);

            if ($this->getUser()) {
                if ($form->isSubmitted() && $form->isValid()) {
                    $title = 'TEMPORARY';
                    $command = new Command();
                    $commands = $commandRepos->findByUserAndTitle($this->getUser(), $title);

                    if (!$commands) {
                        $command->setCreatedAt(new \DateTime())
                            ->setStatus($statusRepos->findByTitle($title))
                            ->setUser($this->getUser());

                        $manager->persist($command);
                        $manager->flush();
                    }

                    $newCommands = $commandRepos->findByUserAndTitle($this->getUser(), $title);

                    $orders = $orderRepos->findByCommandAndProduct($newCommands[0], $product);

                    if (!$orders) {
                        $order = new Order();
                        $order->setCreatedAt(new \DateTime())
                            ->addProduct($product)
                            ->setQuantity($form["quantite"]->getData())
                            ->setCommand($newCommands[0]);
                        $manager->persist($order);
                        $manager->flush();
                    } else {
                        $orders[0]->setQuantity($orders[0]->getQuantity() + $form["quantite"]->getData());
                        $manager->persist($orders[0]);
                        $manager->flush();
                    }

                    return $this->redirectToRoute('cart');
                }
            }

            // Comment form
            $commentForm = $this->createForm(CommentType::class, [
                'action' => $this->generateUrl('product_details', array('id' => $id)),
                'method' => 'POST',
            ]);
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment = new Comment();
                $comment->setCreatedAt(new \DateTime())
                    ->setProduct($product)
                    ->setUser($this->getUser())
                    ->setContent($commentForm["content"]->getData());
                $manager->persist($comment);
                $manager->flush();

                return $this->redirectToRoute('product_details', array('id' => $id));
            }

            return $this->render('product/product_details.html.twig', [
                'product' => $product,
                'products' => $productsLinked,
                'comments' => $comments,
                'max' => $product->getQuantity(),
                'form' => $form->createView(),
                'commentForm' => $commentForm->createView(),
            ]);
        } catch (\Throwable $th) {
            return $this->redirectToRoute('products');
        }
    }

    /**
     * @Route("/produits/{id}", name="category_products")
     */
    public function productForCategory($id, ProductRepository $repos, CategoryRepository $categoryRepos)
    {
        return $this->render('product/index.html.twig', [
            'products' => $repos->findByCategory($id),
            'categories' => $categoryRepos->findAll(),
        ]);
    }

    /**
     * @Route("/admin/produits", name="manage_products")
     */
    public function manageProducts(ProductRepository $repos, PaginatorInterface $paginator, Request $request)
    {
        $paginatedProducts = $paginator->paginate(
            $repos->findAll(), // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );

        return $this->render('admin/products.html.twig', [
            'products' => $paginatedProducts,
        ]);
    }

    /**
     * @Route("/admin/produits/supprimer/{id}", name="remove_product")
     */
    public function removeProduct($id, ProductRepository $repos, OrderRepository $orderRepos, CommentRepository $commentRepos, EntityManagerInterface $manager)
    {
        $product = $repos->find($id);
        $orders = $orderRepos->findByProductId($id);
        $comments = $commentRepos->findByProductId($id);

        for ($i = 0; $i < count($orders); $i++) {
            $manager->remove($orders[$i]);
        }

        for ($i = 0; $i < count($comments); $i++) {
            $manager->remove($comments[$i]);
        }

        $manager->remove($product);
        $manager->flush();

        return $this->redirectToRoute('manage_products');
    }

    /**
     * @Route("/admin/produits/modifier/{id}", name="edit_product")
     */
    public function editProduct($id, ProductRepository $repos, CategoryRepository $categoryRepos, Request $request, EntityManagerinterface $manager)
    {
        $product = $repos->find($id);

        $categories = $categoryRepos->findAll();

        $form = $this->createForm(EditProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $repos->find($id);
            $product = $form->getData();
            $product->setCreatedAt(new \DateTime());

            $manager->persist($product);
            $manager->flush();

            return $this->redirectToRoute('manage_products');
        }

        return $this->render('/admin/edit_product.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/admin/produits/nouveau", name="create_product")
     */
    public function createProduct(CategoryRepository $categoryRepos, Request $request, EntityManagerinterface $manager)
    {
        $product = new Product();

        $categories = $categoryRepos->findAll();

        $form = $this->createForm(EditProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
            $category = $categoryRepos->find($form["category"]->getData());
            $product->setCategory($category);
            $product->setCreatedAt(new \DateTime());

            $manager->persist($product);
            $manager->flush();

            return $this->redirectToRoute('manage_products');
        }

        return $this->render('/admin/edit_product.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'categories' => $categories,
        ]);
    }
}
