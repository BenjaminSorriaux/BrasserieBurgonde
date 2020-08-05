<?php

namespace App\Controller;

use App\Entity\News;
use App\Form\EditNewsType;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewsController extends AbstractController
{
    /**
     * @Route("/actualites", name="news")
     */
    public function index(NewsRepository $repos, PaginatorInterface $paginator, Request $request)
    {
        $paginatedNews = $paginator->paginate(
            $repos->findAllByDate(), // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );
        return $this->render('news/index.html.twig', [
            'news' => $paginatedNews,
        ]);
    }

    /**
     * @Route("admin/actualites", name="manage_news")
     */
    public function manageNews(NewsRepository $repos, PaginatorInterface $paginator, Request $request)
    {
        $paginatedNews = $paginator->paginate(
            $repos->findAllByDate(), // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );

        return $this->render('admin/news.html.twig', [
            'news' => $paginatedNews,
        ]);
    }

    /**
     * @Route("/admin/actualites/supprimer/{id}", name="remove_news")
     */
    public function removeNews($id, NewsRepository $repos, EntityManagerInterface $manager)
    {
        $news = $repos->find($id);

        $manager->remove($news);
        $manager->flush();

        return $this->redirectToRoute('manage_news');
    }

    /**
     * @Route("/admin/actualites/modifier/{id}", name="edit_news")
     */
    public function editNews($id, NewsRepository $repos, EntityManagerInterface $manager, Request $request)
    {
        $news = $repos->find($id);

        $form = $this->createForm(EditNewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $news->setCreatedAt(new \DateTime());

            $manager->persist($news);
            $manager->flush();

            return $this->redirectToRoute('manage_news');
        }

        return $this->render('admin/edit_news.html.twig', [
            'form' => $form->createView(),
            'news' => $news,
        ]);
    }

    /**
     * @Route("/admin/actualites/ajouter", name="create_news")
     */
    public function createNews(NewsRepository $repos, EntityManagerInterface $manager, Request $request)
    {
        $news = new News();

        $form = $this->createForm(EditNewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $news->setCreatedAt(new \DateTime());

            $manager->persist($news);
            $manager->flush();

            return $this->redirectToRoute('manage_news');
        }

        return $this->render('admin/edit_news.html.twig', [
            'news' => $news,
            'form' => $form->createView(),
        ]);
    }
}
