<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\User;
use App\Form\ArticleFormType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
    public function show(Article $article, EntityManagerInterface $manager, Request $request)
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
            'comment' => $comment,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    /**
     * Supprimer un commentaire
     */
    #[Route("/delete-comment/{id}", name: "comment_delete", requirements: ['id' => '\d+'])]
    public function deleteComment(Article $article, Comment $comment, EntityManagerInterface $manager, CommentRepository $commentRepository):Response
    {
        
            $comment = $commentRepository->find($comment->getId());
            $manager->remove($comment);
            $manager->flush();
    
            $this->addFlash('success', 'Votre commentaire à bien été supprimé');
            return $this->redirectToRoute('trick_show', ['id'=>$article->getId()]);
        
    }

    /**
     * Formulaire pour ajouter ou modifier un article
     */
    #[Route("/trick/new", name: "trick_create")]
    #[Route("/trick/{id}/edit", name: "trick_edit")]
    public function formArticle(Request $request, EntityManagerInterface $manager, Article $article=null, CategoryRepository $categoryRepository)
    {
        if(!$article){
            $article = new Article();
        }

        $formArticle = $this->createForm(ArticleFormType::class, $article);
        $formArticle->handleRequest($request);
        
        if($formArticle->isSubmitted() and $formArticle->isValid()) {
            //$article->setUser($this->getUser());
            
            //categories
            $categories = $request->get('article_form')['categories'];
            foreach ($categories as $category_id) {
                $category = $categoryRepository->find($category_id);
                $article->addCategory($category);
            }

            //upload image  
            $images = $formArticle->get('image')->getData();
            foreach($images as $image) {
                //génère un nouveau nom de fichier
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();

                //copie le fichier dans uploads
                $image->move(
                    $this->getParameter('images_directory'),
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
    public function delete(Article $article, EntityManagerInterface $manager, ArticleRepository $articleRepository):Response
    {
        /**
         * @var User
         */

        //récupère l'utilisateur connecté (via symfony) 
        $connectedUser = $this->getUser();
        
        //supprime l'article uniquement celui du user
        if ($connectedUser->getId() == $article->getUser()->getId()) {
            $article = $articleRepository->find($article->getId());
            $manager->remove($article);
            $manager->flush();
    
            $this->addFlash('success', 'Votre article à bien été supprimé');
            return $this->redirectToRoute('home');
        } else {
            $this->addFlash('error', "Vous n'êtes pas autorisé à supprimer cet article car vous n'êtes pas l'auteur");
            return $this->redirectToRoute('home');
        }
        
    }

    /**
     * Supprimer une image dans editer artice
     */
    #[Route("/delete/image/{id}", name: "trick_delete_image", methods:"DELETE")]
    public function deleteImage(Image $image, EntityManagerInterface $manager, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        //on vérifie si le token est valide
        if ($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])) {
            $nom = $image->getName();
            //on supprime le fichier
            unlink($this->getParameter('images_directory') . '/' . $nom);
            // on supprime de la base
            //$em = $this->getDoctrine()->getManager();
            $manager->remove($image);
            $manager->flush();

            //on repond en json
            return new JsonResponse(['success' => 1]);
        } else {
            return new JsonResponse(['error' => 'token Invalide'], 400);
        }
    }
}
