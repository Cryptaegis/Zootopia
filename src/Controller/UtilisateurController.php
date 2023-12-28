<?php


namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/utilisateur")
 */
class UtilisateurController extends AbstractController
{

    
    /**
     * @Route("/", name="utilisateur_index", methods={"GET"})
     */
    public function index(UtilisateurRepository $utilisateurRepository, Session $session): Response
    {
        
        //besoin de droits admin
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $session->set("message", "Merci de vous connecter");
            return $this->redirectToRoute('app_login');
        } else if (in_array('ROLE_ADMIN', $utilisateur->getRoles())) {
            return $this->render('utilisateur/index.html.twig', [
                'utilisateurs' => $utilisateurRepository->findAll(),
            ]);
        }

        return $this->redirectToRoute('home');
    }

    /**
     *@Route("/new", name="utilisateur_new", methods={"GET","POST"})
     */
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher, Session $session, EntityManagerInterface $doctrine): Response
    {

        
        //test de sécurité, un utilisateur connecté ne peut pas s'inscrire
        $utilisateur = $this->getUser();
        if ($utilisateur) {
            $session->set("message", "Vous ne pouvez pas créer un compte lorsque vous êtes connecté");
            return $this->redirectToRoute('membre');
        }

        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);
            
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $doctrine;
            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword()));
            /* uniquement pour créer un admin
                        $role = ['ROLE_ADMIN'];
                        $utilisateur->setRoles($role); */
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            return $this->redirectToRoute('utilisateur_index');
        }

        return $this->render('utilisateur/new.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }
  

    /**
     * @Route("/{id}", name="utilisateur_show", methods={"GET"})
     */
    public function show(Utilisateur $utilisateur): Response
    {
        //accès géré dans le security.yaml
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="utilisateur_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Utilisateur $utilisateur, Session $session, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $doctrine,  $id): Response
    {
        $utilisateur = $this->getUser();
        $utilisateur = $doctrine->getRepository(Utilisateur::class)->find($id);
        // Verify if the current user has the right to modify the targeted user
        if ($id != $id ){
            // un utilisateur ne peut pas en modifier un autre
            $session->set("message", "Vous ne pouvez pas modifier cet utilisateur");
            return $this->redirectToRoute('membre');
        }
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $utilisateur->getPassword()));

            $doctrine->flush();
            return $this->redirectToRoute('utilisateur_index');
        }

        return $this->render('utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="utilisateur_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Utilisateur $utilisateur, Session $session, EntityManagerInterface $doctrine, $id): Response
    {


        $utilisateur = $this->getUser();
        if ($utilisateur->$doctrine != $id) {
            // un utilisateur ne peut pas en supprimer un autre
            $session->set("message", "Vous ne pouvez pas supprimer cet utilisateur");
            return $this->redirectToRoute('membre');
        }

        if ($this->isCsrfTokenValid('delete' . $utilisateur->$doctrine, $request->request->get('_token'))) {
            $entityManager = $this->$doctrine->getManager();
            $entityManager->remove($utilisateur);
            $entityManager->flush();
            // permet de fermer la session utilisateur et d'éviter que l'EntityProvider ne trouve pas la session
            $session = new Session();
            $session->invalidate();
        }

        return $this->redirectToRoute('home');
    }
}
