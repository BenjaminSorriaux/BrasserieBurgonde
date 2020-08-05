<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentController extends AbstractController
{
    /**
     * @Route("/commentaire", name="comment")
     */
    public function index()
    {
        return $this->render('comment/index.html.twig', [
            'controller_name' => 'CommentController',
        ]);
    }

    /**
     * @Route("/admin/commentaires", name="manage_comments")
     */
    public function manageComments(CommentRepository $repos)
    {
        $comments = $repos->findAllByDate();

        return $this->render('admin/comments.html.twig', [
            'comments' => $comments,
        ]);
    }

    /**
     * @Route("admin/commentaire/{id}", name="remove_comment")
     */
    public function removeComment($id, EntityManagerInterface $manager, CommentRepository $repos)
    {
        $comment = $repos->find($id);

        $manager->remove($comment);
        $manager->flush();

        return $this->redirectToRoute('manage_comments');
    }
}
