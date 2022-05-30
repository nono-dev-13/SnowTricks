<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Image;
use App\Form\ArticleType;
use App\Form\CommentType;
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
    public function show(Article $article,EntityManagerInterface $manager, Request $request)
    {

        //Commentaires
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if($commentForm->isSubmitted() && $commentForm->isValid()){
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setArticle($article);

            $manager->persist($comment);
            $manager->flush();

            $this->addFlash('success', 'Votre commentaire à bien été envoyé');
            return $this->redirectToRoute('trick_show', ['id' => $article->getId()]);
        }

        return $this->render('article/show.html.twig', [
            'article'=> $article,
            'commentForm' => $commentForm->createView(),
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
            
            //upload image
            $images = $formArticle->get('image')->getData();
            foreach($images as $image) {
                //génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                //copie le fichier dans uploads
                $image->move(
                    $this->getParameter('images-directory'),
                    $fichier
                );

                //on stock l'image en bdd (son nom)
                $img = new Image();
                $img->setName($fichier);
                $article->addImage($img);
            }

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

    /**
     * Supprimer un article
     */
    #[Route("/delete-trick/{id}", name: "trick_delete")]
    public function delete(Article $article, EntityManagerInterface $manager):Response
    {
        $articles = $manager->getRepository(Article::class)->find($article->getId());
        $manager->remove($articles);
        $manager->flush();

        $this->addFlash('success', 'Votre article à bien été supprimé');
        return $this->redirectToRoute('home');
    }
}
