<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ArticleController extends AbstractController
{
    /**
    * Affiche la home avec les articles
    */
    #[Route('/', name: 'home')]
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
    #[Route("/tricks/add/{add}", name: "load_more_articles", requirements: ['add' => '\d+'])]
    public function loadMoreArticles(ArticleRepository $articleRepo, $add=4)
    {
        $articles = $articleRepo->findBy([],['createdAt' => 'DESC'], 4, $add);
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

    /**
     * Montre un article
     */
    #[Route("/show/{id}", name: "trick_show", requirements: ['id' => '\d+'])]
    public function show(Article $article, Request $request)
    {
        return $this->render('article/show.html.twig', [
            'article'=> $article,
        ]);
    }

    /**
     * Formulaire pour ajouter ou modifier un article
     */
    #[Route("/trick/new", name: "trick_create")]
    #[Route("/trick/{id}/edit", name: "trick_edit")]
    public function formArticle(Request $request, EntityManagerInterface $manager, Article $article=null)
    {
        if(!$article){
            $article = new Article();
        }

        $formArticle = $this->createForm(ArticleType::class, $article);
        $formArticle->handleRequest($request);
        
        if($formArticle->isSubmitted() and $formArticle->isValid()) {
            
            if(!$article->getId()) {
                $article->setCreatedAt(new \DateTimeImmutable());
            }
            
            $manager->persist($article);
            $manager->flush();

            $this->addFlash('success', 'Votre nouvelle article à bien été crée');

            return $this->redirectToRoute('trick_show', ['id'=>$article->getId()]);
        }

        return $this->render('article/create.html.twig', [
            'formArticle'=>$formArticle->createView(),
            'editMode'=> $article->getId()!= null,
            'article'=> $article,
        ]);
    }
}
