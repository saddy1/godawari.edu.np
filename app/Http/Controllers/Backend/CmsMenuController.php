<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CmsMenu;
use App\Models\CmsMenuItem;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsMenuController extends Controller
{
    public function index(Request $request)
    {
        $menus = CmsMenu::orderBy('name')->get();
        $menu = $request->filled('menu')
            ? CmsMenu::with('items.page')->find($request->integer('menu'))
            : $menus->first();
        $pages = CmsPage::orderBy('title')->get(['id', 'title', 'slug']);

        return view('backend.cms.menus.index', compact('menus', 'menu', 'pages'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'location' => Str::slug((string) ($request->input('location') ?: $request->input('name'))),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:80|alpha_dash|unique:cms_menus,location',
        ], [
            'location.unique' => 'This menu location is already used. Use another location such as footer, sidebar, or resources.',
            'location.alpha_dash' => 'Menu location can only contain letters, numbers, dashes, and underscores.',
        ]);
        $data['location'] = strtolower($data['location']);

        $menu = CmsMenu::create($data + ['is_active' => true]);

        return redirect()->route('admin.cms.menus.index', ['menu' => $menu->id])->with('success', 'Menu created.');
    }

    public function update(Request $request, CmsMenu $menu)
    {
        $request->merge([
            'location' => Str::slug((string) ($request->input('location') ?: $request->input('name'))),
        ]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:80|alpha_dash|unique:cms_menus,location,'.$menu->id,
            'is_active' => 'boolean',
        ], [
            'location.unique' => 'This menu location is already used by another menu.',
            'location.alpha_dash' => 'Menu location can only contain letters, numbers, dashes, and underscores.',
        ]);

        $data['location'] = strtolower($data['location']);
        $data['is_active'] = $request->boolean('is_active');
        $menu->update($data);

        return back()->with('success', 'Menu updated.');
    }

    public function storeItem(Request $request, CmsMenu $menu)
    {
        $data = $this->validatedItem($request, $menu);
        $menu->items()->create($data);

        return back()->with('success', 'Menu item added.');
    }

    public function updateItem(Request $request, CmsMenuItem $item)
    {
        $data = $this->validatedItem($request, $item->menu, $item);
        $data['is_active'] = $request->boolean('is_active');
        $item->update($data);

        return back()->with('success', 'Menu item updated.');
    }

    public function destroyItem(CmsMenuItem $item)
    {
        $item->delete();

        return back()->with('success', 'Menu item deleted.');
    }

    private function validatedItem(Request $request, CmsMenu $menu, ?CmsMenuItem $item = null): array
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:cms_menu_items,id',
            'label' => 'required|string|max:255',
            'label_ne' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'subtitle_ne' => 'nullable|string|max:255',
            'type' => 'required|in:page,url',
            'cms_page_id' => 'nullable|required_if:type,page|exists:cms_pages,id',
            'url' => 'nullable|required_if:type,url|string|max:500',
            'target' => 'required|in:_self,_blank',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        if (! empty($data['parent_id'])) {
            $parent = CmsMenuItem::where('cms_menu_id', $menu->id)->whereKey($data['parent_id'])->firstOrFail();
            if ($item && $parent->id === $item->id) {
                $data['parent_id'] = null;
            }
        }

        return $data + ['sort_order' => 0, 'is_active' => true];
    }
}
