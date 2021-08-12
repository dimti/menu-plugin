<?php namespace Dimti\Menu\Components;

use BenFreke\MenuManager\Models\Menu;
use Cms\Classes\ComponentBase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Dimti\Mirsporta\Models\Category;
use RainLab\Translate\Classes\Translator;

class MenuPageInjector extends ComponentBase
{

    protected $menu;

    protected $cacheTime = 180;

    protected $pageVariableName;

    protected $menuId;

    public function componentDetails()
    {
        return [
            'name' => 'MenuInjector Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'cacheTime' => [
                'title' => 'Cache time in seconds',
                'description' => 'Cache time',
                'default' => 0,
                'validationPattern' => '^[0-9]+$',
                'type' => 'string',
            ],
            'pageVariableName' => [
                'title' => 'Page variable name',
                'description' => 'Page variable name which menu is assigned to',
                'type' => 'string',
            ],
            'menuId' => [
                'title' => 'Menu id',
                'description' => 'Menu id to load',
                'validationPattern' => '^[0-9]+$',
                'type' => 'string',
            ],
        ];
    }

    public function init()
    {
        $this->cacheTime = $this->property('cacheTime', 0);

        $pageVariableName = $this->property('pageVariableName');

        $this->pageVariableName = $pageVariableName;

        // try to resolve menu id from theme config
        $this->menuId = $this->getTheme()->getConfigValue(
            $pageVariableName,
            $this->property('menuId')
        );
    }

    public function onRun()
    {
        $this->initMenu();

        $this->injectMenu();
    }

    /**
     * Fill up $menus
     */
    protected function initMenu()
    {
        if ($this->getMenu($this->property('menuId')) != null) {
            $this->menu = \Cache::tags(self::class)->remember(
                $this->getMenuCacheKey(),
                $this->cacheTime,
                function () {
                    $menu = $this->getMenu($this->menuId);

                    $menu['url'] = $menu->getLinkHref();

                    // init link href. It's a heavy operation so it gotta be cached
                    foreach ($menu->children as &$menuItem) {
                        $menuItem->url = $menuItem->getLinkHref();

                        $menuItem->children = $menuItem->children->filter(fn($item) => $item->enabled);


                        $this->fillPhotoMenuItem($menuItem);

                        foreach ($menuItem->children as &$secondaryMenuItem) {
                            $secondaryMenuItem->url = $secondaryMenuItem->getLinkHref();

                            $secondaryMenuItem->children = $secondaryMenuItem->children->filter(fn($item) => $item->enabled);

                            foreach ($secondaryMenuItem->children as &$tertiaryMenuItem) {
                                $tertiaryMenuItem->url = $tertiaryMenuItem->getLinkHref();
                            }

                        }

                        $this->fillOfflineMallCategoriesToMenu($menuItem);

                    }

                    $this->connectionFullNestedTreeCategoriesToMenu($menu);

                    return $menu->toArray();
                }
            );
        }
    }

    private function connectionFullNestedTreeCategoriesToMenu($menu)
    {
        if ($menu->full_nested_tree) {
            $categories = Category::getNested()->all();
            foreach ($categories as $category) {
                $this->connectionCategoriesToMenu($menu, $category);
            }
        }
    }

    private function connectionCategoriesToMenu($menuItem, $category)
    {
        $firstMenuItem = [
            'id' => $category->id,
            'title' => $category->name,
            'url' => $this->controller->pageUrl(
                'category',
                ['slug' => $category->getFullSlug()]
            ),
            'children' => [],
            'image' => $category->icon ? $category->icon->getPath() : '',
            'special' => [
                'image' => $category->image ? $category->image->getPath() : '',
                'text' => '',
                'amount' => '',
            ],
        ];

        if (!$menuItem->disables_child_categories) {
            foreach ($category->children as $categoryChildren) {
                if (!$categoryChildren->disables_menu) {
                    $secondaryMenuItem = [
                        'id' => $categoryChildren->id,
                        'title' => $categoryChildren->name,
                        'url' => $this->controller->pageUrl(
                            'category',
                            ['slug' => $categoryChildren->getFullSlug()]
                        ),
                        'children' => [],
                    ];

                    foreach ($categoryChildren->children as $secondCategoryChildren) {
                        if (!$secondCategoryChildren->disables_menu) {
                            $tertiaryMenuItem = [
                                'id' => $secondCategoryChildren->id,
                                'title' => $secondCategoryChildren->name,
                                'url' => $this->controller->pageUrl(
                                    'category',
                                    ['slug' => $secondCategoryChildren->getFullSlug()]
                                ),
                            ];

                            $secondaryMenuItem['children'][] = $tertiaryMenuItem;
                        }
                    }

                    $firstMenuItem['children'][] = $secondaryMenuItem;
                }
            }
        }
        $menuItem->children->add($firstMenuItem);
    }

    private function fillOfflineMallCategoriesToMenu($menuItem)
    {
        if ($menuItem->category_id) {
            $category = Category::whereId($menuItem->category_id)->first()->allChildren(true)->getNested()->first();

            $menuItem['url'] = $this->controller->pageUrl('category', ['slug' => $category->getFullSlug()]);

            $this->connectionCategoriesToMenu($menuItem, $category);

        }
    }
    private function fillPhotoMenuItem($menuItem)
    {
        if ($menuItem->photo) {
            $menuItem['special'] = [
                'image' => $menuItem->photo->getPath(),
                'text' => $menuItem->message_photo ?: null,
                'amount' => $menuItem->amount ?: null,
            ];
        }
    }
    /**
     * Inject menus into page
     */
    protected function injectMenu()
    {
        $this->page[$this->pageVariableName] = $this->menu;
    }

    /**
     * Get menu or it's children
     *
     * @param $menuId
     *
     * @return Menu|null
     */
    protected function getMenu($menuId)
    {
        try {
            $menu = Menu::allRoot()->whereId($menuId)->enabled()->get()->first();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('No query results for menuId: ' . $menuId);
        }

        return $menu;
    }

    /**
     * Get cache key for menu
     *
     * @return string
     */
    protected function getMenuCacheKey()
    {
        // menu table checksum
        $checkSum = $this->getMenuTableChecksum();

        return self::class . '_menuCacheKey_' . $this->cacheTime . '_' . $this->menuId . '_' . $checkSum;
    }

    /**
     * @return integer|null
     */
    protected function getMenuTableChecksum()
    {
        return \Cache::tags(self::class)->remember(self::class . '_menutablechecksum', 1, function () {
            return Menu::tableChecksum();
        });
    }
}
