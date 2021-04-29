<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\Users;

class UserDataPersister implements DataPersisterInterface {

    private $entityManager;
    private $userPasswordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $userPasswordEncoder) {

        $this->entityManager = $entityManager;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function supports($data): bool
    {
        return $data instanceof Users;
    }

    /**
     * @param Users $data
     */
    public function persist($data)
    {
        if ($data->getPassNotEncrypted()) {
            $data->setPassword(
                $this->userPasswordEncoder->encodePassword($data, $data->getPassNotEncrypted())
            );

            $data->eraseCredentials();
        }
        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }

    public function remove($data)
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }

}
