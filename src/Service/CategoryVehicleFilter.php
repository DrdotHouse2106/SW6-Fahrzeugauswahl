<?php declare(strict_types=1);

namespace Ulber\FahrzeugSchnellauswahl\Service;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\Tree\Tree;
use Shopware\Core\Content\Category\Tree\TreeItem;
use Shopware\Core\Content\ProductStream\Service\AbstractProductStreamBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Blendet im Menü Kategorien aus, die für das gewählte Fahrzeug keine Produkte haben.
 *
 * Ausgeblendet wird nur, was überhaupt Produkte hat, aber keine fürs Fahrzeug – Inhaltsseiten ohne Produkte
 * (z. B. „Über uns“), Links und Ordner mit sichtbaren Unterkategorien bleiben stehen.
 */
class CategoryVehicleFilter
{
    /** @var array<string, bool> */
    private array $memo = [];

    public function __construct(
        private readonly SalesChannelRepository $productRepository,
        private readonly AbstractProductStreamBuilder $streamBuilder
    ) {
    }

    /**
     * @param list<string> $optionIds gewähltes Fahrzeug plus „Passt zu allen Fahrzeugen“
     */
    public function filterTree(Tree $tree, array $optionIds, SalesChannelContext $context): void
    {
        $tree->setTree($this->filterItems($tree->getTree(), $optionIds, $context));
    }

    /**
     * @param array<TreeItem> $items
     *
     * @return array<TreeItem>
     */
    private function filterItems(array $items, array $optionIds, SalesChannelContext $context): array
    {
        $kept = [];

        foreach ($items as $key => $item) {
            $hadChildren = $item->getChildren() !== [];
            $item->setChildren($this->filterItems($item->getChildren(), $optionIds, $context));

            if ($this->keep($item->getCategory(), $hadChildren, $item->getChildren() !== [], $optionIds, $context)) {
                $kept[$key] = $item;
            }
        }

        return $kept;
    }

    private function keep(CategoryEntity $category, bool $hadChildren, bool $hasChildren, array $optionIds, SalesChannelContext $context): bool
    {
        if ($hasChildren || $category->getType() === CategoryDefinition::TYPE_LINK) {
            return true;
        }

        if ($category->getType() === CategoryDefinition::TYPE_FOLDER) {
            // Ordner, deren Unterkategorien alle ausgeblendet wurden
            return !$hadChildren;
        }

        // Inhaltsseite ohne Produkte → nicht anfassen
        if (!$this->hasProducts($category, null, $context)) {
            return !$hadChildren;
        }

        return $this->hasProducts($category, $optionIds, $context);
    }

    private function hasProducts(CategoryEntity $category, ?array $optionIds, SalesChannelContext $context): bool
    {
        $key = $category->getId() . '-' . ($optionIds === null ? 'all' : implode(',', $optionIds));
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);
        $criteria->setTitle('vehicle-switcher::category-has-products');

        $streamId = $category->getProductStreamId();
        if ($category->getProductAssignmentType() === CategoryDefinition::PRODUCT_ASSIGNMENT_TYPE_PRODUCT_STREAM && $streamId !== null) {
            $this->streamBuilder->enrichCriteria($criteria, $streamId, $context->getContext());
        } else {
            $criteria->addFilter(new EqualsFilter('product.categoriesRo.id', $category->getId()));
        }

        if ($optionIds !== null) {
            $criteria->addFilter(new EqualsAnyFilter('product.properties.id', $optionIds));
        }

        try {
            $result = $this->productRepository->searchIds($criteria, $context)->getIds() !== [];
        } catch (\Throwable) {
            // Im Zweifel anzeigen
            $result = true;
        }

        return $this->memo[$key] = $result;
    }
}
