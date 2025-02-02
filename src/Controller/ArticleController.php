<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    #[Route('/article/generate', name: 'generate_article')]
    public function generateArticle(EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $str_now = date('Y-m-d H:i:s', time());
        $article->setTitre('Roman' . $str_now);
        $content = file_get_contents('http://loripsum.net/api');
        $article->setTexte($content);
        $article->setPublie(true);
        $article->setDate(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $str_now));
        $entityManager->persist($article);
        $entityManager->flush();
        return new Response('Saved new article with id ' . $article->getId());
    }

    #[Route('/article/list', name: 'list_article')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();
        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/article/show/{id}', name: 'show_article')]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);
        if (!$article) {
            throw $this->createNotFoundException('The article does not exist');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('delete_article', ['id' => $article->getId()]))
            ->setMethod('DELETE')
            ->add('delete', SubmitType::class, ['label' => 'Delete'])
            ->getForm();

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    #[Route('/article/new', name: 'new_article')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('list_article');
        }

        return $this->render('article/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/article/edit/{id}', name: 'edit_article')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);
        if (!$article) {
            throw $this->createNotFoundException('The article does not exist');
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('list_article');
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/article/delete/{id}', name: 'delete_article', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article deleted !');

        return $this->redirectToRoute('list_article');
    }
}