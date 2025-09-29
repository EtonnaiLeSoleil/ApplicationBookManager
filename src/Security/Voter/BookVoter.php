<?php
namespace App\Security;

use App\Entity\Book;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookVoter extends Voter
{
    public const EDIT = 'BOOK_EDIT';
    public const DELETE = 'BOOK_DELETE';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Book;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user) {
            return false; // utilisateur non connecté
        }

        // Un admin peut tout faire
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Book $book */
        $book = $subject;

        return match ($attribute) {
            self::EDIT, self::DELETE => $book->getUser() && $book->getUser()->getId() === $user->getId(),
            default => false,
        };
    }
}
