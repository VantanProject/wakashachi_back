<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\MenuItem;
use App\Models\MenuItemText;
use App\Models\MenuItemMerch;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    public function store(Request $request)
    {
        $menu = $request['menu'];

        $user = Auth::user();

        $createdMenu = Menu::create([
            'company_id' => $user->company_id,
            'name' => $menu['name'],
            'color' => $menu['color'],
        ]);

        foreach ($menu['page'] as $pageData) {
            $menuPage = $createdMenu->pages()->create([
                'count' => $pageData['count'],
            ]);

            foreach ($pageData['items'] as $itemData) {
                $menuItem = $menuPage->items()->create([
                    'width' => $itemData['width'],
                    'height' => $itemData['height'],
                    'top' => $itemData['top'],
                    'left' => $itemData['left'],
                ]);

                if ($itemData['type'] === 'merch') {
                    $menuItem->merch()->create([
                        'merch_id' => $itemData['merch_id'],
                    ]);
                } 
                if ($itemData['type'] === 'text') {
                    $menuItem->text()->create([
                        'text' => $itemData['text'],
                        'color' => $itemData['color'],
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'メニューが正常に追加されました！',
        ]);
    }
}