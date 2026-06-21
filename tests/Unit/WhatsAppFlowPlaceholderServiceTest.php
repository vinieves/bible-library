<?php

namespace Tests\Unit;

use App\Services\WhatsAppFlowPlaceholderService;
use PHPUnit\Framework\TestCase;

class WhatsAppFlowPlaceholderServiceTest extends TestCase
{
    public function test_it_replaces_nome_with_first_name_from_push_name(): void
    {
        $service = new WhatsAppFlowPlaceholderService;

        $result = $service->render('Holaa {nome}, ¿cómo estás?', 'Raul abster');

        $this->assertSame('Holaa Raul, ¿cómo estás?', $result);
    }

    public function test_it_leaves_empty_when_contact_name_is_missing(): void
    {
        $service = new WhatsAppFlowPlaceholderService;

        $result = $service->render('Holaa {nome}!', null);

        $this->assertSame('Holaa !', $result);
    }
}
