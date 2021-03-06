<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Form\DataTransformerInterface;

class JsonToTagTransformer implements DataTransformerInterface
{
    private TagRepository $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    public function transform($tags)
    {
        $array = [];
        foreach ($tags as $tag) {
            $array[] = $tag->getLabel();
        }

        return json_encode($array);
    }

    public function reverseTransform($json)
    {
        $tags = [];
        foreach (json_decode($json) as $raw) {
            $label = trim($raw);

            if ($label == '') {
                continue;
            }

            $tag = $this->tagRepository->findOneBy(['label' => $label]);

            if (!$tag) {
                $tag = new Tag();
                $tag->setLabel($label);
            }

            if (!\in_array($tag, $tags, false)) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
