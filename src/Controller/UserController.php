<?php

namespace App\Controller;

use Swift_Mailer;
use App\Entity\User;
use App\Entity\Command;
use App\Entity\Product;
use App\Form\LoginType;
use App\Form\ContactType;
use App\Form\RemoveOrderType;
use App\Form\RegistrationType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Form\ForgottenPasswordType;
use App\Form\ResetPasswordType;
use App\Repository\OrderRepository;
use App\Repository\StatusRepository;
use App\Repository\CommandRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractController
{
    private $mailer;

    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @Route("/user", name="user")
     */
    public function index()
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function contact(Request $request)
    {
        $form = $this->createForm(ContactType::class);

        return $this->render('user/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/envoi_email", name="send_email")
     */
    public function sendEmail(Request $request, \Swift_Mailer $mailer)
    {
        $data = $request->request->get('contact');
        if ($data['subject'] == 1) {
            $message = (new \Swift_Message("Question concernant une commande"))
                ->setFrom($data['email'])
                ->setTo('brasserie.test@gmail.com');

            $message->setBody(
                "<h1>Vous avez été contacté par ". 
                $data['prenom'] . " " . $data['nom'] . 
                '.</h1><p style="font-size: 16px">Voici son message : <br/>' . $data['message'] . 
                "</p><p>Pour le recontacter, voici son adresse email : " . $data['email'] .
                '</p><img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
            );
        }
        else {
            $message = (new \Swift_Message("Question concernant la brasserie"))
                ->setFrom($data['email'])
                ->setTo('brasserie.test@gmail.com');
            
            $message->setBody(
                "<h1>Vous avez été contacté par ". 
                $data['prenom'] . " " . $data['nom'] . 
                '.</h1><p style="font-size: 16px">Voici son message : <br/>' . $data['message'] . 
                "</p><p>Pour le recontacter, voici son adresse email : " . $data['email'] .
                '</p><img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
            );
        }

        $mailer->send($message);

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/connexion", name="login")
     */
    public function login()
    {
        $form = $this->createForm(LoginType::class);

        return $this->render('user/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/inscription", name="registration")
     */
    public function register()
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        return $this->render('user/registration.html.twig', [
            'form' =>$form->createView(),
        ]);
    }

    /**
     * @Route("/confirmation_compte", name="account_confirm")
     */
    public function accountConfirm(Request $request, \Swift_Mailer $mailer, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder, RoleRepository $roleRepos)
    {
        $data = $request->request->get('registration');
        $user = new User();

        $message = (new \Swift_Message("Création de compte pour la Brasserie Burgonde"))
            ->setFrom('brasserie.test@gmail.com')
            ->setTo($data['email']);    
            
        $message->setBody(
            "<h1>Bienvenue ". $data['firstname'] . " " . $data['lastname'] . 
            ' ! </h1> <p style="font-size: 16px">Votre compte à la Brasserie Burgonde a été créé, vous pouvez dès maintenant vous connecter à celui-ci. 
            A présent, vous aurez la possibilité de commander nos produits et partager votre avis les concernant.<p>' .
            '<img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
        );

        $mailer->send($message);

        $user->setRole($roleRepos->findByName("USER"))
            ->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setEmail($data['email'])
            ->setPassword($encoder->encodePassword($user, $data['password']));

        $manager->persist($user);
        $manager->flush();

        return $this->redirectToRoute('login');
    }

    /**
     * @Route("/mot_de_passe_oublie", name="forgotten_password")
     */
    public function forgottenPassword(Request $request, UserRepository $repos, \Swift_Mailer $mailer) {
        $user = new User();
        $form = $this->createForm(ForgottenPasswordType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form["email"]->getData();

            if ($repos->findByEmail($email)[0])
            {
                $message = (new \Swift_Message("Réinitialisation de mot de passe"))
                    ->setFrom('brasserie.test@gmail.com')
                    ->setTo($email);    
                    
                $message->setBody(
                    '<p style="font-size: 16px">Une demande de changement de mot de passe a été effectuée.</p>' .
                    '<p style="font-size: 16px">Pour changer votre mot de passe, veuillez cliquer sur le lien suivant :</p>' .
                    '<p style="font-size: 16px;"><a href="http://127.0.0.1:8000/reinitialiser_mot_de_passe/' . $repos->findByEmail($email)[0]->getId() . '">Réinitialiser mon mot de passe</a></p>' .
                    '<img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
                );

                $mailer->send($message);

            }
            else {
                $this->addFlash('danger', 'Email Inconnu !');
            }

            return $this->redirectToRoute('home');
        }

        return $this->render('/user/forgotten_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reinitialiser_mot_de_passe/{id}", name="reset_password")
     */
    public function resetPassword($id, Request $request, EntityManagerInterface $manager, UserRepository $repos, UserPasswordEncoderInterface $encoder) {
        $user = new User();
        $form = $this->createForm(ResetPasswordType::class, $user);

        $userToEdit = $repos->find($id);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $encoded = $encoder->encodePassword($user, $user->getPassword());

            $userToEdit->setPassword($encoded);

            $manager->persist($userToEdit);
            $manager->flush();

            return $this->redirectToRoute('login');
        }

        return $this->render("user/reset_password.html.twig", [
            'form' => $form->createView(),
        ]);
    }







    /**
     * @Route("/admin_change_password/{id}", name="admin_change_password")
     */
    public function adminChangePassword($id, Request $request, UserRepository $repos, \Swift_Mailer $mailer) {
        $user = $repos->find($id);

        $message = (new \Swift_Message("Changement de mot de passe"))
            ->setFrom('brasserie.test@gmail.com')
            ->setTo($user->getEmail());    
            
        $message->setBody(
            '<p style="font-size: 16px">Bonjour, en tant que nouvel administrateur, un changement de mot de passe est nécessaire.</p>' .
            '<p style="font-size: 16px">Pour changer votre mot de passe, veuillez cliquer sur le lien suivant :</p>' .
            '<p style="font-size: 16px;"><a href="http://127.0.0.1:8000/nouveau_mot_de_passe/' . $user->getId() . '">Changer mon mot de passe</a></p>' .
            '<img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
        );

        $mailer->send($message);

        return $this->redirectToRoute('manage_admins');
    }

    /**
     * @Route("/nouveau_mot_de_passe/{id}", name="adminNew_password")
     */
    public function adminNewPassword($id, Request $request, EntityManagerInterface $manager, UserRepository $repos, UserPasswordEncoderInterface $encoder) {
        $user = new User();
        $form = $this->createForm(ResetPasswordType::class, $user);

        $userToEdit = $repos->find($id);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $encoded = $encoder->encodePassword($user, $user->getPassword());

            $userToEdit->setPassword($encoded);

            $manager->persist($userToEdit);
            $manager->flush();

            return $this->redirectToRoute('login');
        }

        return $this->render("user/reset_password.html.twig", [
            'form' => $form->createView(),
        ]);
    }









    /**
     * @Route("/deconnexion", name="logout")
     */
    public function logout() {}

    /**
     * @Route("/panier", name="cart")
     */
    public function cart(CommandRepository $commandRepos, ProductRepository $productRepository)
    {
        $tempCommands = $commandRepos->findByUserAndTitle($this->getUser(), "TEMPORARY");

        $command = new Command();

        if (count($tempCommands)) {
            $command = $tempCommands[0];
        }

        $commands = $commandRepos->findByUser($this->getUser());

        if (!$command) {
            return $this->redirectToRoute('home');
        }

        $products = $productRepository->findAll();

        $orders = $command->getOrders();

        return $this->render('user/cart.html.twig', [
            'orders' => $orders,
            'command' => $command,
            'commands' => $commands,
            'total' => 0,
            'totalWaitingCommand' => 0,
            'totalConfirmedCommand' => 0,
            'products' => $products,
        ]);
    }

    /**
     * @Route("/panier/supprimer", name="cart_remove")
     */
    public function cartRemove($id, CommandRepository $commandRepos, OrderRepository $repos, EntityManagerInterface $manager)
    {
        $title = 'TEMPORARY';
        $command = $commandRepos->findByUserAndTitle($this->getUser(), $title)[0];

        $order = $repos->findByProductId($id)[0];

        $manager->remove($order);
        $manager->persist($command);
        $manager->flush();

        return $this->redirectToRoute('cart');
    }

    /**
     * @Route("/commander", name="request_command")
     */
    public function requestCommand(CommandRepository $commandRepos, StatusRepository $statusRepos, EntityManagerInterface $manager, \Swift_Mailer $mailer)
    {
        $command = $commandRepos->findByUserAndTitle($this->getUser(), "TEMPORARY")[0];
        $command->setStatus($statusRepos->findByTitle("WAITING"));
        $command->setCreatedAt(new \DateTime());

        $orders = $command->getOrders();

        $total = 0;

        foreach ($orders as $order) {
            $total += $order->getQuantity() * $order->getProduct()[0]->getPrice();
        }

        $message = (new \Swift_Message("Commande effectuée"))
            ->setFrom('brasserie.test@gmail.com')
            ->setTo($this->getUser()->getEmail());    
            
        $message->setBody(
            '<p style="font-size: 16px">Bonjour '. $this->getUser()->getFirstname() . " " . $this->getUser()->getLastname() . 
            ' ! </p><p style="font-size: 16px">Merci d\'avoir passé une commande chez nous !</p>' .
            '<p style="font-size: 16px">Votre commande a été enregistrée et est actuellement en attente de confirmation.</p>' .
            '<p style="font-size: 16px">Dès que ceci sera fait, vous recevrez un nouveau mail qui vous donnera les informations nécessaires au retrait de celle-ci.</p>' .
            '<p style="font-size: 16px">Informations sur votre commande :</p>' .
            '<p style="font-size: 16px">Numéro de commande : '. $command->getId() .'</p>' .
            '<p style="font-size: 16px">Montant = ' . $total . ' €</p>' .
            '<img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
        );

        $mailer->send($message);

        $manager->persist($command);
        $manager->flush();

        return $this->redirectToRoute('cart');
    }

    /**
     * @Route("/admin/commandes", name="manage_commands")
     */
    public function manageCommands(CommandRepository $repos)
    {
        $commands = $repos->findByTitle("WAITING");
        $confirmedCommands = $repos->findByTitle("CONFIRMED");
        foreach ($confirmedCommands as $confirmedCommand) {
            array_push($commands, $confirmedCommand);
        }
        return $this->render('/admin/commands.html.twig', [
            'commands' => $commands,
            'total' => 0,
        ]);
    }

    /**
     * @Route("/admin/commandes/confirmer/{id}", name="confirm_command")
     */
    public function confirmCommand($id, CommandRepository $repos, StatusRepository $statusRepos, EntityManagerInterface $manager, \Swift_Mailer $mailer)
    {
        $command = $repos->find($id);
        $command->setStatus($statusRepos->findByTitle("CONFIRMED"));

        $message = (new \Swift_Message("Commande confirmée"))
            ->setFrom('brasserie.test@gmail.com')
            ->setTo($command->getUser()->getEmail());    
            
        $message->setBody(
            '<p style="font-size: 16px">Bonjour '. $command->getUser()->getFirstname() . " " . $command->getUser()->getLastname() . ' ! </p>' .
            '<p style="font-size: 16px">Votre commande a été confirmée !</p><p style="font-size: 16px">Vous pourrez venir la chercher dès que vous le désirez.</p>' .
            '<p style="font-size: 16px">Vous pouvez retrouver nos horaires sur notre site web sur la page "Nous contacter".</p>' .
            '<p style="font-size: 16px">Lorsque vous viendrez récupérer votre commande, vous devrez présenter cet email.</p>' .
            '<img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
        );

        $mailer->send($message);

        $orders = $command->getOrders();

        foreach ($orders as $order) {
            $product = $order->getProduct()[0];
            $product->setQuantity($product->getQuantity() - $order->getQuantity());
            $manager->persist($product);
        }

        $manager->persist($command);
        $manager->flush();
        
        return $this->redirectToRoute('manage_commands');
    }

    /**
     * @Route("/admin/commandes/supprimer/{id}", name="remove_command")
     */
    public function removeCommand($id, OrderRepository $orderRepos, CommandRepository $repos, EntityManagerInterface $manager, \Swift_Mailer $mailer)
    {
        $command = $repos->find($id);

        $message = (new \Swift_Message("Commande refusée"))
            ->setFrom('brasserie.test@gmail.com')
            ->setTo($command->getUser()->getEmail());    
            
        $message->setBody(
            '<p style="font-size: 16px">Bonjour '. $command->getUser()->getFirstname() . " " . $command->getUser()->getLastname() . 
            ' ! </p><p style="font-size: 16px">Votre commande a été refusée !</p><p style="font-size: 16px">Pour avoir des détails sur la raison du refus, vous pouvez nous contacter via notre site web.</p>' .
            'Si vous souhaitez avoir une conversation téléphonique, vous trouverez notre numéro de téléphone sur notre site web sur la page "Nous contacter".</p>' .
            '<img width="500" src="' . $message->embed(\Swift_Image::fromPath('images/brasserie_medium.png')) . '" alt="Image" />', 'text/html'
        );

        $mailer->send($message);

        $orders = $orderRepos->findByCommandId($id);

        foreach ($orders as $order) {
            $manager->remove($order);
        }

        $manager->remove($repos->find($id));
        $manager->flush();

        return $this->redirectToRoute('manage_commands');
    }

    /**
     * @Route("admin/administrateurs", name="manage_admins")
     */
    public function manageAdmins(UserRepository $repos)
    {
        $admins = $repos->findByRole("ADMIN");

        return $this->render('admin/admins.html.twig', [
            'admins' => $admins,
        ]);
    }

    /**
     * @Route("admin/administrateurs/nouveau", name="create_admin")
     */
    public function createAdmin(RoleRepository $roleRepos, UserRepository $userRepos, Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder)
    {
        $admin = new User();

        $form = $this->createForm(RegistrationType::class, $admin);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $admin = $form->getData();
            $encoded = $encoder->encodePassword($admin, $admin->getPassword());
            $admin->setPassword($encoded)
                ->setRole($roleRepos->findByName("ADMIN"));

            $manager->persist($admin);
            $manager->flush();

            return $this->redirectToRoute('admin_change_password', [
                'id' => $admin->getId(),
            ]);
        }

        return $this->render('admin/create_admin.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}