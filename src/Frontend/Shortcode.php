<?php
declare(strict_types=1);

namespace AFE\Frontend;

//This makes [afe_form id="1"] work.
class Shortcode
{
    private Renderer $renderer;

    public function __construct()
    {
        $this->renderer = new Renderer();
    }

    /**
     * Register the [afe_form] shortcode.
     */
    public function register(): void
    {
        add_shortcode('afe_form', [$this, 'handleShortcode']);
    }

    /**
     * Handle [afe_form id="1"].
     *
     * @param array<string,mixed> $atts
     * @return string
     */
    public function handleShortcode($atts): string
    {
        $atts = shortcode_atts(
            [
                'id' => 0,
            ],
            $atts,
            'afe_form'
        );

        $formId = (int) $atts['id'];

        if ($formId <= 0) {
            return '';
        }

        return $this->renderer->renderForm($formId);
    }
}
