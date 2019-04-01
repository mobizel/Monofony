<?php

namespace spec\App\Entity;

use App\Entity\AdminUser;
use App\Entity\AdminUserInterface;
use Doctrine\Common\Collections\Collection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\User\Model\User;
use Sylius\Component\User\Model\UserInterface;

class AdminUserSpec extends ObjectBehavior
{
    function it_extends_a_base_user_model(): void
    {
        $this->shouldHaveType(User::class);
    }

    function it_implements_an_admin_user_interface(): void
    {
        $this->shouldImplement(AdminUserInterface::class);
    }

    function it_implements_a_user_interface(): void
    {
        $this->shouldImplement(UserInterface::class);
    }

    function it_has_a_generated_salt_by_default(): void
    {
        $this->getSalt()->shouldNotReturn(null);
    }

    function it_initializes_oauth_accounts_collection_by_default(): void
    {
        $this->getOAuthAccounts()->shouldHaveType(Collection::class);
    }

    function its_not_enabled_by_default()
    {
        $this->shouldNotBeEnabled();
    }

    function it_has_no_first_name_by_default(): void
    {
        $this->getFirstName()->shouldReturn(null);
    }

    function its_first_name_is_mutable(): void
    {
        $this->setFirstName('John');
        $this->getFirstName()->shouldReturn('John');
    }

    function it_has_no_last_name_by_default(): void
    {
        $this->getLastName()->shouldReturn(null);
    }

    function its_last_name_is_mutable(): void
    {
        $this->setLastName('Doe');
        $this->getLastName()->shouldReturn('Doe');
    }
}
