<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\User;
use App\Entity\Video;
use App\Form\ArticleFormType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
    #[Route("/show/{slug}", name: "trick_show", requirements: ['slug' => '[a-z0-9\-]*'])]
    //public function show(Article $article, EntityManagerInterface $manager, PaginatorInterface $paginator, Request $request, CommentRepository $commentRepository, string $slug)
    public function show(ArticleRepository $articleRepository, EntityManagerInterface $manager, PaginatorInterface $paginator, Request $request, CommentRepository $commentRepository, string $slug)

    {
        $article = $articleRepository->findBySlug($request->get('slug'));
        //Commentaires
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if($commentForm->isSubmitted() && $commentForm->isValid()){
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setArticle($article);
            $comment->setUser($this->getUser());

            $manager->persist($comment);
            $manager->flush();

            $this->addFlash('success', 'Votre commentaire à bien été envoyé');
            //return $this->redirectToRoute('trick_show', ['id' => $article->getId()]);

            if ($article->getSlug() !== $slug){
                return $this->redirectToRoute('trick_show',[
                    'slug' => $article->getSlug(),
                ], 301);
            }
        }

        $page = $request->query->getInt('page', 1);
        //pagination
        $pagination = $commentRepository->getPagination($article->getId(),$page);

        return $this->render('article/show.html.twig', [
            'article'=> $article,
            'comment' => $comment,
            'commentForm' => $commentForm->createView(),
            'pagination' => $pagination,
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
     * Formulaire pour ajouter un article
     */
    #[Route("/trick/new", name: "trick_create")]
    public function formAddArticle(Request $request, EntityManagerInterface $manager, Article $article=null, CategoryRepository $categoryRepository, VideoRepository $videoRepository)
    {

        if(!$article){
            $article = new Article();
            $article->addVideo((new Video()));
        }

        $formAddArticle = $this->createForm(ArticleFormType::class, $article);
        $formAddArticle->handleRequest($request);
        
        //modifie l'article uniquement celui du user
        
        if($formAddArticle->isSubmitted() and $formAddArticle->isValid()) {
            // si  le permier champs n'est pas rempli alors on ne l'enregistre pas en bdd
            $listVideos = $article->getVideos();
            $firstVideo = $listVideos[0];
            
            if(empty($firstVideo->getUrl())){
                $article->removeVideo($firstVideo);
            }
            
            
            //$videos = $request->get('article_form')['videos'];
            //$videos = new ArrayCollection();
            foreach ($article->getVideos() as $video) {
                //on remplace le permier params
                $url = str_replace("watch?v=", "embed/", $video->getUrl());
                
                //on enlève tout ce qu'il y a après le &
                //$url = strstr($url, '&', true);
                
                $video->setUrl($url);

                //$video = parse_url($video->getUrl());
                //$video = parse_str( parse_url( $video->getUrl(), PHP_URL_QUERY ), $link );
                //$videos->add($video);
            }
            
            $categories = $request->get('article_form')['categories'];
            foreach ($categories as $category_id) {
                $category = $categoryRepository->find($category_id);
                $article->addCategory($category);
            }
            
            $images = $formAddArticle->get('image')->getData();
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

            $article->setUser($this->getUser());

            if(!$article->getId()) {
                $article->setCreatedAt(new \DateTimeImmutable());
            }

            $manager->persist($article);
            $manager->flush();

            $this->addFlash('success', 'Votre nouvelle article à bien été crée');

            return $this->redirectToRoute('trick_show',[
                'slug' => $article->getSlug(),
            ]);
                
        }
        
        return $this->render('article/create.html.twig', [
            'formAddArticle'=>$formAddArticle->createView(),
            'editMode'=> $article->getId()!= null,
            'article'=> $article,
        ]);
    }

    /**
     * Formulaire pour modifier un article
     */
    #[Route("/trick/{id}/edit", name: "trick_edit")]
    public function formEditArticle(Request $request, EntityManagerInterface $manager, Article $article=null, CategoryRepository $categoryRepository)
    {

        if(!$article){
            $article = new Article();
        }

        $formEditArticle = $this->createForm(ArticleFormType::class, $article);
        $formEditArticle->handleRequest($request);
        
        /**
         * récupère l'utilisateur connecté (via symfony) 
         * @var User
         */
        $connectedUser = $this->getUser();
        
        //modifie l'article uniquement celui du user
        
        if($formEditArticle->isSubmitted() and $formEditArticle->isValid()) {
            //$article->setUser($this->getUser());

            if ($connectedUser->getId() == $article->getUser()->getId() || $connectedUser->getRoles(["ROLE_ADMIN"])) {
                
                foreach ($article->getVideos() as $video) {
                    if (!empty($video->getUrl())) {
                        //on remplace le permier params
                        $url = str_replace("watch?v=", "embed/", $video->getUrl());
                        //on enlève tout ce qu'il y a après le &
                        //$url = strstr($url, '&', true);
                        $video->setUrl($url);
                    }
                    
                }

                $categories = $request->get('article_form')['categories'];
                foreach ($categories as $category_id) {
                    $category = $categoryRepository->find($category_id);
                    $article->addCategory($category);
                }  
                
                $images = $formEditArticle->get('image')->getData();
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

                $this->addFlash('success', 'Votre nouvelle article à bien été modifié');

                return $this->redirectToRoute('trick_show', ['id'=>$article->getId()]);
            
            } else {
                $this->addFlash('error', 'Vous ne pouvez pas modifier cet article');
    
                return $this->redirectToRoute('trick_show', ['id'=>$article->getId()]);
            }   
                
        }
        

        return $this->render('article/edit.html.twig', [
            'formEditArticle'=>$formEditArticle->createView(),
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
        if ($connectedUser->getId() == $article->getUser()->getId() || $connectedUser->getRoles(["ROLE_ADMIN"])) {
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
