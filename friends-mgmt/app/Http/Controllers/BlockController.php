<?php

namespace App\Http\Controllers;

use App\Block;
use App\Http\Controllers\Controller;

class BlockController extends Controller
{
    /**
     * Create a new block record.
     *
     * @param  $requestorId
     * @param  $targetId
     * @return Block
     */
    public static function create($requestorId, $targetId) {
        return Block::create([
            'requestor_id' => $requestorId,
            'target_id' => $targetId,
        ]);
    }

    /**
     * Check if the given requestor id can block the given target-
     * id.
     *
     * @param  $requestorId
     * @param  $targetId
     * @return bool
     */
    public static function canBlock($requestorId, $targetId) {
        if ($requestorId == $targetId
            || self::hasBlocked($requestorId, $targetId)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check if the given requestor id has blocked the given target-
     * id.
     *
     * @param  $requestorId
     * @param  $targetId
     * @return bool
     */
    public static function hasBlocked($requestorId, $targetId) {
        return self::getBlockRecord($requestorId, $targetId) != null;
    }

    private static function getBlockRecord($requestorId, $targetId) {
        return Block::where([
            'requestor_id' => $requestorId, 'target_id' => $targetId
        ])->first();
    }
}
