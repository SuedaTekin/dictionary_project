<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminRegistrationController extends AbstractController
{
    #[Route('/create-admin', name: 'create_admin')]
    public function index(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $user->setEmail('admin@sozluk.com'); // Giriş yaparken kullanacağın mail
        $user->setRoles(['ROLE_ADMIN']); // Seni admin yapan kritik satır
        
        // Şifreni burada belirle
        $hashedPassword = $passwordHasher->hashPassword($user, 'admin123');
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return new Response('Admin kullanıcısı başarıyla oluşturuldu! Artık /login sayfasından giriş yapabilirsiniz.');
    }
}