<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ArticleController extends AbstractController
{
    /**
    * Affiche la home avec les articles
    */
    #[Route('/', name: 'app_article')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $listArticles = $articleRepository->findBy([],['createdAt' => 'DESC'], 4, 0);
        
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'listArticles' => $listArticles
        ]);
    }

    /**
    * Affiche la liste des articles sur le loadMore
    */

    #[Route("/blog/add/{add}", name: "load_more_articles", requirements: ['add' => '\d+'])]
    public function loadMoreArticles(ArticleRepository $articleRepo, $add=4)
    {
        $articles = $articleRepo->findBy([],['createdAt' => 'DESC'], 4, 0);
        $response = $this->render('article/loadMoreArticles.html.twig', [
            'articles' => $articles
        ]);
        $html = $response->getContent();

        $articlesTotal = $articleRepo->count([]);
        return new JsonResponse([
            'html' => $html,
            'articlesTotal' => $articlesTotal
        ]);

    }
}
