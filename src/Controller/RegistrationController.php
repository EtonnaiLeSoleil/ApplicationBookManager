#[Route('/register', name: 'app_register')]
public function register(
Request $request,
UserPasswordHasherInterface $passwordHasher,
EntityManagerInterface $entityManager
): Response {
$user = new User();
$form = $this->createForm(RegistrationFormType::class, $user);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
// Hash le mot de passe
$user->setPassword(
$passwordHasher->hashPassword(
$user,
$form->get('plainPassword')->getData()
)
);

$entityManager->persist($user);
$entityManager->flush();

$this->addFlash('success', '✅ Inscription réussie, vous pouvez vous connecter !');
return $this->redirectToRoute('app_login');
}

return $this->render('registration/register.html.twig', [
'registrationForm' => $form->createView(),
]);
}
