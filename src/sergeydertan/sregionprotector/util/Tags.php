<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util;

abstract class Tags
{
    const MIN_X_TAG = "min_x";
    const MIN_Y_TAG = "min_y";
    const MIN_Z_TAG = "min_z";
    const MAX_X_TAG = "max_x";
    const MAX_Y_TAG = "max_y";
    const MAX_Z_TAG = "max_z";
    const X_TAG = "x";
    const Y_TAG = "y";
    const Z_TAG = "z";
    const YAW_TAG = "yaw";
    const PITCH_TAG = "pitch";
    const OWNERS_TAG = "owners";
    const MEMBERS_TAG = "members";
    const NAME_TAG = "name";
    const LEVEL_TAG = "level";
    const FLAGS_TAG = "flags";
    const CREATOR_TAG = "creator";
    const STATE_TAG = "state";
    const PRICE_TAG = "price";
    const POSITION_TAG = "position";
    const DATA_TAG = "data";
    const ID_TAG = "id";
    const REGION_TAG = "region";
    const IS_MOVABLE_TAG = "isMovable";
    const PRIORITY_TAG = "priority";
    const BLOCK_ORE = "Ore";
    const CUSTOM_NAME_TAG = "CustomName";
    /**
     * UI
     */
    const IS_UI_ITEM_TAG = "gui-item";
    const OPEN_PAGE_TAG = "open-page";
    const CURRENT_PAGE_NAME_TAG = "current-page-name";
    const CURRENT_PAGE_NUMBER_TAG = "current-page-number";
    const NEXT_PAGE_TAG = "next-page";
    const PREVIOUS_PAGE_TAG = "previous-page";
    const REFRESH_PAGE_TAG = "refresh-page";
    const FLAG_ID_TAG = "flag-id";
    const TARGET_NAME_TAG = "target";
    const REMOVE_REGION_TAG = "remove-region";
}
