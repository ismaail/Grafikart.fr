<?php

namespace App\Tests\Controller;

use App\Domain\Auth\User;
use App\Tests\FixturesTrait;
use App\Tests\WebTestCase;

class PasswordControllerTest extends WebTestCase
{

    const RESET_PASSWORD_PATH = '/password/new';
    const RESET_PASSWORD_BUTTON = 'M\'envoyer les instructions';

    use FixturesTrait;

    public function testResetPasswordIsReachableFromLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $crawler = $this->client->click($crawler->selectLink('Mot de passe oublié ?')->link());
        $this->assertEquals('Mot de passe oublié', $crawler->filter('h1')->text());
    }

    public function testResetPasswordBlockBadEmails(): void
    {
        $crawler = $this->client->request('GET', self::RESET_PASSWORD_PATH);
        $this->expectFormErrors(0);
        $form = $crawler->selectButton(self::RESET_PASSWORD_BUTTON)->form();
        $form->setValues([
            'email' => 'lol hacker',
        ]);
        $this->client->submit($form);
        $this->expectFormErrors(1);
    }

    public function testResetPasswordShouldSendAnEmail(): void
    {
        /** @var array<string,User> $users */
        $users = $this->loadFixtures(['users']);

        $crawler = $this->client->request('GET', self::RESET_PASSWORD_PATH);
        $form = $crawler->selectButton(self::RESET_PASSWORD_BUTTON)->form();
        $form->setValues([
            'email' => $users['user1']->getEmail(),
        ]);
        $this->client->submit($form);
        $this->expectFormErrors(0);
        $this->assertEmailCount(1);
    }

    public function testResetPasswordShouldBlockRepeat(): void
    {
        /** @var array<string,User> $users */
        $users = $this->loadFixtures(['users']);

        $crawler = $this->client->request('GET', self::RESET_PASSWORD_PATH);

        // Je demande un nouveau mot de passe
        $form = $crawler->selectButton(self::RESET_PASSWORD_BUTTON)->form();
        $form->setValues([
            'email' => $users['user1']->getEmail(),
        ]);
        $this->client->submit($form);

        // Je demande encore un nouveau mot de passe
        $crawler = $this->client->request('GET', self::RESET_PASSWORD_PATH);
        $this->expectFormErrors(0);
        $form = $crawler->selectButton(self::RESET_PASSWORD_BUTTON)->form();
        $form->setValues([
            'email' => $users['user1']->getEmail(),
        ]);
        $this->client->submit($form);
        $this->expectErrorAlert();
    }

    public function testResetPasswordShouldWorkWithOldPasswordAttempt(): void
    {
        /** @var array<string,User> $users */
        $users = $this->loadFixtures(['password-reset']);
        $crawler = $this->client->request('GET', self::RESET_PASSWORD_PATH);
        $form = $crawler->selectButton(self::RESET_PASSWORD_BUTTON)->form();
        $form->setValues([
            'email' => $users['user1']->getEmail(),
        ]);
        $this->client->submit($form);
        $this->assertEmailCount(1);
    }

    public function testResetPasswordAfterSuccess(): void
    {
        // TODO : Tester que l'on peut relancer une demande de réinitialisation après une demande complété
    }

    public function testResetPasswordConfirmChangePassword(): void
    {
        // TODO : Vérifier que le mot de passe de l'utilisateur est bien changé
    }

    public function testResetPasswordConfirmExpired(): void
    {
        // TODO : Vérifier que l'on soit bien redirigé si le token est invalid
    }
}