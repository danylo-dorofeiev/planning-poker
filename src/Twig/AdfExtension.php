<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;

class AdfExtension extends AbstractExtension
{
    public function convertAdfToMarkdown(?array $adf): ?string
    {
        if ($adf === null) {
            return null;
        }

        return $this->convertNodes($adf['content'] ?? []);
    }

    private function convertNodes(array $nodes): string
    {
        $result = [];

        foreach ($nodes as $node) {
            $type = $node['type'] ?? '';

            if ($type === 'text') {
                $text = $node['text'] ?? '';

                foreach ($node['marks'] ?? [] as $mark) {
                    $text = match ($mark['type'] ?? '') {
                        'strong' => "**{$text}**",
                        'em' => "*{$text}*",
                        'strike' => "~~{$text}~~",
                        'code' => "`{$text}`",
                        'link' => '[' . $text . ']('
                            . ($mark['attrs']['href'] ?? '') . ')',
                        default => $text,
                    };
                }

                $result[] = $text;
                continue;
            }

            $content = $this->convertNodes($node['content'] ?? []);

            $result[] = match ($type) {
                'paragraph' => $content,
                'heading' => str_repeat('#', $node['attrs']['level'] ?? 1)
                    . ' ' . $content,
                'bulletList', 'orderedList' => $content,
                'listItem' => '- ' . $content,
                'blockquote' => '> ' . str_replace("\n", "\n> ", $content),
                'codeBlock' => "```\n{$content}\n```",
                'hardBreak' => "\n",
                'rule' => '---',
                default => $content,
            };
        }

        return implode("\n\n", array_filter(
            $result,
            static fn ($value) => $value !== ''
        ));
    }
}
