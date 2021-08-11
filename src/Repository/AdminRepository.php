<?php

namespace App\Repository;

use App\Entity\Admin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
//use Symfony\Component\Security\Core\Encoder\MigratingPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\MigratingPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;



/**
 * @method Admin|null find($id, $lockMode = null, $lockVersion = null)
 * @method Admin|null findOneBy(array $criteria, array $orderBy = null)
 * @method Admin[]    findAll()
 * @method Admin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private $passwordHasher;
//    private $encoder;

    public function __construct(
        ManagerRegistry $registry,
        UserPasswordEncoderInterface $passwordHasher
//        MigratingPasswordEncoder $encoder
    )
    {
        parent::__construct($registry, Admin::class);
        $this->passwordHasher = $passwordHasher;
//        $this->encoder = $encoder;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Admin) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function createUser($name, $password)
    {
        $user = new Admin();

        $encoded_password = $this->passwordHasher->encodePassword($user, $password);
//        $encoded_password = $this->encoder->encodePassword($user, $password);

        $user->setUsername($name);
        $user->setPassword($encoded_password);
        $user->setRoles(['ROLE_ADMIN']);

        $this->_em->persist($user);
        $this->_em->flush();
    }
}