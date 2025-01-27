<?php

/*

Plumrocket Inc.

NOTICE OF LICENSE

This source file is subject to the End-user License Agreement
that is available through the world-wide-web at this URL:
http://wiki.plumrocket.net/wiki/EULA
If you are unable to obtain it through the world-wide-web, please
send an email to support@plumrocket.com so we can send you a copy immediately.

@package    Plumrocket_Base-v1.x.x
@copyright  Copyright (c) 2015 Plumrocket Inc. (http://www.plumrocket.com)
@license    http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
*/
class Plumrocket_Base_Model_Product extends Mage_Core_Model_Abstract
{
    protected static $_prefs = null;
    protected $_name = null;
    protected $_session = null;
    protected $_pref = null;
    protected $_dbCacheTime = 3;
    protected $_sUrl;
    protected $_test = false;
    protected $_customer = null;
    protected static $_edit = null;
    const V = 1;
    const PR = 'Plumrocket_';
    public function _construct()
    {
        parent::_construct();
        $this->_init('plumbase/product');
        $this->_sUrl = implode('', array_map('chr', array('104', '116', '116', '112', '115', '58', '47', '47', '115', '116', '111', '114', '101', '46', '112', '108', '117', '109', '114', '111', '99', '107', '101', '116', '46', '99', '111', '109', '47', '105', '108', '103', '47', '112', '105', '110', '103', '98', '97', '99', '107', '47')));
    }
    public function load($id, $field = null)
    {
        $this->_initInstall();
        if (is_null($field) && !is_numeric($id)) {
            $this->_name = $id;
            return parent::load($this->getSignature(), 'signature');
        }
        return parent::load($id, $field);
    }
    public function loadByPref($pref)
    {
        $this->setPref($pref);
        return $this->load($this->getName());
    }
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function setPref($pref)
    {
        $this->_pref = $pref;
        $items = (array) Mage::getConfig()->getNode('global/helpers')->children();
        foreach ($items as $key => $item) {
            if ($key == $pref) {
                $t = explode('_', (string) $item->class);
                $this->setName($t[1]);
            }
        }
        return $this;
    }
    public static function getEdit()
    {
        if (is_null(self::$_edit)) {
            $conf = Mage::getConfig();
            $ep = 'Enterprise';
            self::$_edit = $conf->getModuleConfig("Enterprise_Enterprise") || $conf->getModuleConfig("Enterprise_AdminGws") || $conf->getModuleConfig("Enterprise_Checkout") || $conf->getModuleConfig("Enterprise_Customer") ? $ep : 'Community';
        }
        return self::$_edit;
    }
    public function getPref()
    {
        if (is_null($this->_pref)) {
            if (is_null(self::$_prefs)) {
                self::$_prefs = array();
                $items = (array) Mage::getConfig()->getNode('global/helpers')->children();
                foreach ($items as $key => $item) {
                    $c = (string) $item->class;
                    self::$_prefs[$c] = $key;
                }
            }
            $c = self::PR . "{$this->_name}_Helper";
            $this->_pref = isset(self::$_prefs[$c]) ? self::$_prefs[$c] : false;
        }
        return $this->_pref;
    }
    public function isCached()
    {
        if ($this->_test) {
            return false;
        }
        return $this->getDate() > date('Y-m-d H:i:s') && $this->getDate() < date('Y-m-d H:i:s', time() + 2592000);
    }
    public function isInStock()
    {
        return $this->getStatus() && $this->getStatus() % 100 == 0;
    }
    public function getDescription()
    {
        if ($this->isInStock()) {
            return implode('', array_map('chr', array(0 => "67", 1 => "111", 2 => "110", 3 => "103", 4 => "114", 5 => "97", 6 => "116", 7 => "117", 8 => "108", 9 => "97", 10 => "116", 11 => "105", 12 => "111", 13 => "110", 14 => "115", 15 => "33", 16 => "32", 17 => "89", 18 => "111", 19 => "117", 20 => "114", 21 => "32", 22 => "115", 23 => "101", 24 => "114", 25 => "105", 26 => "97", 27 => "108", 28 => "32", 29 => "107", 30 => "101", 31 => "121", 32 => "32", 33 => "105", 34 => "115", 35 => "32", 36 => "110", 37 => "111", 38 => "119", 39 => "32", 40 => "97", 41 => "99", 42 => "116", 43 => "105", 44 => "118", 45 => "97", 46 => "116", 47 => "101", 48 => "100", 49 => "46", 50 => "32", 51 => "84", 52 => "104", 53 => "97", 54 => "110", 55 => "107", 56 => "32", 57 => "121", 58 => "111", 59 => "117", 60 => "32", 61 => "102", 62 => "111", 63 => "114", 64 => "32", 65 => "99", 66 => "104", 67 => "111", 68 => "111", 69 => "115", 70 => "105", 71 => "110", 72 => "103", 73 => "32", 74 => "80", 75 => "108", 76 => "117", 77 => "109", 78 => "114", 79 => "111", 80 => "99", 81 => "107", 82 => "101", 83 => "116", 84 => "32", 85 => "73", 86 => "110", 87 => "99", 88 => "32", 89 => "97", 90 => "115", 91 => "32", 92 => "121", 93 => "111", 94 => "117", 95 => "114", 96 => "32", 97 => "77", 98 => "97", 99 => "103", 100 => "101", 101 => "110", 102 => "116", 103 => "111", 104 => "32", 105 => "101", 106 => "120", 107 => "116", 108 => "101", 109 => "110", 110 => "115", 111 => "105", 112 => "111", 113 => "110", 114 => "32", 115 => "112", 116 => "114", 117 => "111", 118 => "118", 119 => "105", 120 => "100", 121 => "101", 122 => "114", 123 => "33")));
        }
        if (!$this->getSession()) {
            return implode('', array_map('chr', array(0 => "83", 1 => "101", 2 => "114", 3 => "105", 4 => "97", 5 => "108", 6 => "32", 7 => "107", 8 => "101", 9 => "121", 10 => "32", 11 => "105", 12 => "115", 13 => "32", 14 => "109", 15 => "105", 16 => "115", 17 => "115", 18 => "105", 19 => "110", 20 => "103", 21 => "46", 22 => "32", 23 => "80", 24 => "108", 25 => "101", 26 => "97", 27 => "115", 28 => "101", 29 => "32", 30 => "108", 31 => "111", 32 => "103", 33 => "105", 34 => "110", 35 => "32", 36 => "116", 37 => "111", 38 => "32", 39 => "121", 40 => "111", 41 => "117", 42 => "114", 43 => "32", 44 => "97", 45 => "99", 46 => "99", 47 => "111", 48 => "117", 49 => "110", 50 => "116", 51 => "32", 52 => "97", 53 => "116", 54 => "32", 55 => "60", 56 => "97", 57 => "32", 58 => "116", 59 => "97", 60 => "114", 61 => "103", 62 => "101", 63 => "116", 64 => "61", 65 => "34", 66 => "95", 67 => "98", 68 => "108", 69 => "97", 70 => "110", 71 => "107", 72 => "34", 73 => "32", 74 => "104", 75 => "114", 76 => "101", 77 => "102", 78 => "61", 79 => "34", 80 => "104", 81 => "116", 82 => "116", 83 => "112", 84 => "115", 85 => "58", 86 => "47", 87 => "47", 88 => "115", 89 => "116", 90 => "111", 91 => "114", 92 => "101", 93 => "46", 94 => "112", 95 => "108", 96 => "117", 97 => "109", 98 => "114", 99 => "111", 100 => "99", 101 => "107", 102 => "101", 103 => "116", 104 => "46", 105 => "99", 106 => "111", 107 => "109", 108 => "47", 109 => "100", 110 => "111", 111 => "119", 112 => "110", 113 => "108", 114 => "111", 115 => "97", 116 => "100", 117 => "97", 118 => "98", 119 => "108", 120 => "101", 121 => "47", 122 => "99", 123 => "117", 124 => "115", 125 => "116", 126 => "111", 127 => "109", 128 => "101", 129 => "114", 130 => "47", 131 => "112", 132 => "114", 133 => "111", 134 => "100", 135 => "117", 136 => "99", 137 => "116", 138 => "115", 139 => "47", 140 => "34", 141 => "62", 142 => "104", 143 => "116", 144 => "116", 145 => "112", 146 => "115", 147 => "58", 148 => "47", 149 => "47", 150 => "115", 151 => "116", 152 => "111", 153 => "114", 154 => "101", 155 => "46", 156 => "112", 157 => "108", 158 => "117", 159 => "109", 160 => "114", 161 => "111", 162 => "99", 163 => "107", 164 => "101", 165 => "116", 166 => "46", 167 => "99", 168 => "111", 169 => "109", 170 => "47", 171 => "60", 172 => "47", 173 => "97", 174 => "62", 175 => "32", 176 => "116", 177 => "111", 178 => "32", 179 => "99", 180 => "111", 181 => "112", 182 => "121", 183 => "32", 184 => "121", 185 => "111", 186 => "117", 187 => "114", 188 => "32", 189 => "115", 190 => "101", 191 => "114", 192 => "105", 193 => "97", 194 => "108", 195 => "32", 196 => "107", 197 => "101", 198 => "121", 199 => "32", 200 => "102", 201 => "111", 202 => "114", 203 => "32", 204 => "116", 205 => "104", 206 => "105", 207 => "115", 208 => "32", 209 => "112", 210 => "114", 211 => "111", 212 => "100", 213 => "117", 214 => "99", 215 => "116", 216 => "46", 217 => "32", 218 => "82", 219 => "101", 220 => "97", 221 => "100", 222 => "32", 223 => "116", 224 => "104", 225 => "105", 226 => "115", 227 => "32", 228 => "60", 229 => "97", 230 => "32", 231 => "116", 232 => "97", 233 => "114", 234 => "103", 235 => "101", 236 => "116", 237 => "61", 238 => "34", 239 => "95", 240 => "98", 241 => "108", 242 => "97", 243 => "110", 244 => "107", 245 => "34", 246 => "32", 247 => "104", 248 => "114", 249 => "101", 250 => "102", 251 => "61", 252 => "34", 253 => "104", 254 => "116", 255 => "116", 256 => "112", 257 => "58", 258 => "47", 259 => "47", 260 => "119", 261 => "105", 262 => "107", 263 => "105", 264 => "46", 265 => "112", 266 => "108", 267 => "117", 268 => "109", 269 => "114", 270 => "111", 271 => "99", 272 => "107", 273 => "101", 274 => "116", 275 => "46", 276 => "99", 277 => "111", 278 => "109", 279 => "47", 280 => "119", 281 => "105", 282 => "107", 283 => "105", 284 => "47", 285 => "76", 286 => "105", 287 => "99", 288 => "101", 289 => "110", 290 => "115", 291 => "101", 292 => "95", 293 => "73", 294 => "110", 295 => "115", 296 => "116", 297 => "97", 298 => "108", 299 => "108", 300 => "97", 301 => "116", 302 => "105", 303 => "111", 304 => "110", 305 => "34", 306 => "62", 307 => "119", 308 => "105", 309 => "107", 310 => "105", 311 => "32", 312 => "97", 313 => "114", 314 => "116", 315 => "105", 316 => "99", 317 => "108", 318 => "101", 319 => "60", 320 => "47", 321 => "97", 322 => "62", 323 => "32", 324 => "102", 325 => "111", 326 => "114", 327 => "32", 328 => "109", 329 => "111", 330 => "114", 331 => "101", 332 => "32", 333 => "105", 334 => "110", 335 => "102", 336 => "111", 337 => "46")));
        }
        if (!$this->isInStock()) {
            $status = (int) $this->getStatus();
            switch ($status) {
                case 503:
                    return implode('', array_map('chr', array(0 => "89", 1 => "111", 2 => "117", 3 => "114", 4 => "32", 5 => "115", 6 => "101", 7 => "114", 8 => "105", 9 => "97", 10 => "108", 11 => "32", 12 => "107", 13 => "101", 14 => "121", 15 => "32", 16 => "105", 17 => "115", 18 => "32", 19 => "110", 20 => "111", 21 => "116", 22 => "32", 23 => "118", 24 => "97", 25 => "108", 26 => "105", 27 => "100", 28 => "32", 29 => "102", 30 => "111", 31 => "114", 32 => "32", 33 => "77", 34 => "97", 35 => "103", 36 => "101", 37 => "110", 38 => "116", 39 => "111", 40 => "32", 41 => "69", 42 => "110", 43 => "116", 44 => "101", 45 => "114", 46 => "112", 47 => "114", 48 => "105", 49 => "115", 50 => "101", 51 => "32", 52 => "69", 53 => "100", 54 => "105", 55 => "116", 56 => "105", 57 => "111", 58 => "110", 59 => "46", 60 => "32", 61 => "80", 62 => "108", 63 => "101", 64 => "97", 65 => "115", 66 => "101", 67 => "32", 68 => "112", 69 => "117", 70 => "114", 71 => "99", 72 => "104", 73 => "97", 74 => "115", 75 => "101", 76 => "32", 77 => "77", 78 => "97", 79 => "103", 80 => "101", 81 => "110", 82 => "116", 83 => "111", 84 => "32", 85 => "69", 86 => "110", 87 => "116", 88 => "101", 89 => "114", 90 => "112", 91 => "114", 92 => "105", 93 => "115", 94 => "101", 95 => "32", 96 => "69", 97 => "100", 98 => "105", 99 => "116", 100 => "105", 101 => "111", 102 => "110", 103 => "32", 104 => "108", 105 => "105", 106 => "99", 107 => "101", 108 => "110", 109 => "115", 110 => "101", 111 => "32", 112 => "102", 113 => "111", 114 => "114", 115 => "32", 116 => "116", 117 => "104", 118 => "105", 119 => "115", 120 => "32", 121 => "112", 122 => "114", 123 => "111", 124 => "100", 125 => "117", 126 => "99", 127 => "116", 128 => "32", 129 => "97", 130 => "116", 131 => "32", 132 => "60", 133 => "97", 134 => "32", 135 => "104", 136 => "114", 137 => "101", 138 => "102", 139 => "61", 140 => "34", 141 => "104", 142 => "116", 143 => "116", 144 => "112", 145 => "115", 146 => "58", 147 => "47", 148 => "47", 149 => "115", 150 => "116", 151 => "111", 152 => "114", 153 => "101", 154 => "46", 155 => "112", 156 => "108", 157 => "117", 158 => "109", 159 => "114", 160 => "111", 161 => "99", 162 => "107", 163 => "101", 164 => "116", 165 => "46", 166 => "99", 167 => "111", 168 => "109", 169 => "47", 170 => "34", 171 => "32", 172 => "116", 173 => "97", 174 => "114", 175 => "103", 176 => "101", 177 => "116", 178 => "61", 179 => "34", 180 => "95", 181 => "98", 182 => "108", 183 => "97", 184 => "110", 185 => "107", 186 => "34", 187 => "62", 188 => "104", 189 => "116", 190 => "116", 191 => "112", 192 => "115", 193 => "58", 194 => "47", 195 => "47", 196 => "115", 197 => "116", 198 => "111", 199 => "114", 200 => "101", 201 => "46", 202 => "112", 203 => "108", 204 => "117", 205 => "109", 206 => "114", 207 => "111", 208 => "99", 209 => "107", 210 => "101", 211 => "116", 212 => "46", 213 => "99", 214 => "111", 215 => "109", 216 => "47", 217 => "60", 218 => "47", 219 => "97", 220 => "62")));
                default:
                    return implode('', array_map('chr', array(0 => "83", 1 => "101", 2 => "114", 3 => "105", 4 => "97", 5 => "108", 6 => "32", 7 => "107", 8 => "101", 9 => "121", 10 => "32", 11 => "105", 12 => "115", 13 => "32", 14 => "110", 15 => "111", 16 => "116", 17 => "32", 18 => "118", 19 => "97", 20 => "108", 21 => "105", 22 => "100", 23 => "32", 24 => "102", 25 => "111", 26 => "114", 27 => "32", 28 => "116", 29 => "104", 30 => "105", 31 => "115", 32 => "32", 33 => "100", 34 => "111", 35 => "109", 36 => "97", 37 => "105", 38 => "110", 39 => "46", 40 => "32", 41 => "80", 42 => "108", 43 => "101", 44 => "97", 45 => "115", 46 => "101", 47 => "32", 48 => "103", 49 => "111", 50 => "32", 51 => "116", 52 => "111", 53 => "32", 54 => "60", 55 => "97", 56 => "32", 57 => "104", 58 => "114", 59 => "101", 60 => "102", 61 => "61", 62 => "34", 63 => "104", 64 => "116", 65 => "116", 66 => "112", 67 => "115", 68 => "58", 69 => "47", 70 => "47", 71 => "115", 72 => "116", 73 => "111", 74 => "114", 75 => "101", 76 => "46", 77 => "112", 78 => "108", 79 => "117", 80 => "109", 81 => "114", 82 => "111", 83 => "99", 84 => "107", 85 => "101", 86 => "116", 87 => "46", 88 => "99", 89 => "111", 90 => "109", 91 => "47", 92 => "34", 93 => "32", 94 => "116", 95 => "97", 96 => "114", 97 => "103", 98 => "101", 99 => "116", 100 => "61", 101 => "34", 102 => "95", 103 => "98", 104 => "108", 105 => "97", 106 => "110", 107 => "107", 108 => "34", 109 => "62", 110 => "104", 111 => "116", 112 => "116", 113 => "112", 114 => "115", 115 => "58", 116 => "47", 117 => "47", 118 => "115", 119 => "116", 120 => "111", 121 => "114", 122 => "101", 123 => "46", 124 => "112", 125 => "108", 126 => "117", 127 => "109", 128 => "114", 129 => "111", 130 => "99", 131 => "107", 132 => "101", 133 => "116", 134 => "46", 135 => "99", 136 => "111", 137 => "109", 138 => "47", 139 => "60", 140 => "47", 141 => "97", 142 => "62", 143 => "32", 144 => "116", 145 => "111", 146 => "32", 147 => "112", 148 => "117", 149 => "114", 150 => "99", 151 => "104", 152 => "97", 153 => "115", 154 => "101", 155 => "32", 156 => "110", 157 => "101", 158 => "119", 159 => "32", 160 => "108", 161 => "105", 162 => "99", 163 => "101", 164 => "110", 165 => "115", 166 => "101", 167 => "32", 168 => "102", 169 => "111", 170 => "114", 171 => "32", 172 => "108", 173 => "105", 174 => "118", 175 => "101", 176 => "32", 177 => "115", 178 => "105", 179 => "116", 180 => "101", 181 => "46", 182 => "32", 183 => "32", 184 => "84", 185 => "101", 186 => "115", 187 => "116", 188 => "105", 189 => "110", 190 => "103", 191 => "32", 192 => "111", 193 => "114", 194 => "32", 195 => "100", 196 => "101", 197 => "118", 198 => "101", 199 => "108", 200 => "111", 201 => "112", 202 => "109", 203 => "101", 204 => "110", 205 => "116", 206 => "32", 207 => "115", 208 => "117", 209 => "98", 210 => "100", 211 => "111", 212 => "109", 213 => "97", 214 => "105", 215 => "110", 216 => "115", 217 => "32", 218 => "99", 219 => "97", 220 => "110", 221 => "32", 222 => "98", 223 => "101", 224 => "32", 225 => "97", 226 => "100", 227 => "100", 228 => "101", 229 => "100", 230 => "32", 231 => "116", 232 => "111", 233 => "32", 234 => "121", 235 => "111", 236 => "117", 237 => "114", 238 => "32", 239 => "108", 240 => "105", 241 => "99", 242 => "101", 243 => "110", 244 => "115", 245 => "101", 246 => "32", 247 => "102", 248 => "114", 249 => "101", 250 => "101", 251 => "32", 252 => "111", 253 => "102", 254 => "32", 255 => "99", 256 => "104", 257 => "97", 258 => "114", 259 => "103", 260 => "101", 261 => "46", 262 => "32", 263 => "82", 264 => "101", 265 => "97", 266 => "100", 267 => "32", 268 => "116", 269 => "104", 270 => "105", 271 => "115", 272 => "32", 273 => "60", 274 => "97", 275 => "32", 276 => "104", 277 => "114", 278 => "101", 279 => "102", 280 => "61", 281 => "34", 282 => "104", 283 => "116", 284 => "116", 285 => "112", 286 => "58", 287 => "47", 288 => "47", 289 => "119", 290 => "105", 291 => "107", 292 => "105", 293 => "46", 294 => "112", 295 => "108", 296 => "117", 297 => "109", 298 => "114", 299 => "111", 300 => "99", 301 => "107", 302 => "101", 303 => "116", 304 => "46", 305 => "99", 306 => "111", 307 => "109", 308 => "47", 309 => "119", 310 => "105", 311 => "107", 312 => "105", 313 => "47", 314 => "85", 315 => "112", 316 => "100", 317 => "97", 318 => "116", 319 => "105", 320 => "110", 321 => "103", 322 => "95", 323 => "76", 324 => "105", 325 => "99", 326 => "101", 327 => "110", 328 => "115", 329 => "101", 330 => "95", 331 => "68", 332 => "111", 333 => "109", 334 => "97", 335 => "105", 336 => "110", 337 => "115", 338 => "34", 339 => "32", 340 => "32", 341 => "116", 342 => "97", 343 => "114", 344 => "103", 345 => "101", 346 => "116", 347 => "61", 348 => "34", 349 => "95", 350 => "98", 351 => "108", 352 => "97", 353 => "110", 354 => "107", 355 => "34", 356 => "62", 357 => "119", 358 => "105", 359 => "107", 360 => "105", 361 => "32", 362 => "97", 363 => "114", 364 => "116", 365 => "105", 366 => "99", 367 => "108", 368 => "101", 369 => "60", 370 => "47", 371 => "97", 372 => "62", 373 => "32", 374 => "102", 375 => "111", 376 => "114", 377 => "32", 378 => "109", 379 => "111", 380 => "114", 381 => "101", 382 => "32", 383 => "105", 384 => "110", 385 => "102", 386 => "111", 387 => "46")));
            }
        }
        return null;
    }
    public function currentCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = 1;
        }
        return "customer";
    }
    public function enabled()
    {
        if ($this->getPref()) {
            $helper = $this->getHelper();
            if (method_exists($helper, 'moduleEnabled')) {
                foreach (Mage::app()->getStores() as $store) {
                    if ($store->getIsActive() && $helper->moduleEnabled($store->getId())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function getSignature()
    {
        return md5($this->_name . $this->getSession());
    }
    public function getSession()
    {
        if (!$this->hasData('session')) {
            $this->setSession(Mage::getStoreConfig($this->getPref() . '/general/' . "serial", 0));
        }
        return preg_replace("/\\s+/", "", $this->getData('session'));
    }
    public function loadSession()
    {
        $session = '';
        try {
            $data = array('edition' => self::getEdit(), 'base_urls' => $this->getBaseU(), 'name' => $this->getName(), 'customer' => $this->getCustomer(), 'title' => $this->getTitle());
            $xml = $this->_getContent($this->_sUrl . 'session/', $data);
            $session = isset($xml['data']) ? $xml['data'] : null;
        } catch (Exception $e) {
            if ($this->_test) {
                echo $e->getMessage();
                exit;
            }
        }
        $this->setSession($session);
        $this->saveStatus($this->getSimpleStatus());
        return $session;
    }
    public function getHelper()
    {
        return Mage::helper($this->getPref());
    }
    public function getCustomer()
    {
        $helper = $this->getHelper();
        if (method_exists($helper, 'getCustomerKey')) {
            return $helper->getCustomerKey();
        }
        return null;
    }
    public function getBaseU()
    {
        $k = "web/secure/base_url";
        $_us = array();
        $u = Mage::getStoreConfig($k, 0);
        $_us[$u] = $u;
        foreach (Mage::app()->getStores() as $store) {
            if ($store->getIsActive()) {
                $u = Mage::getStoreConfig($k, $store->getId());
                $_us[$u] = $u;
            }
        }
        return array_values($_us);
    }
    public function checkStatus()
    {
        $session = $this->getSession();
        try {
            $data = array('edition' => self::getEdit(), 'session' => $session, 'base_urls' => $this->getBaseU(), 'name' => $this->getName(), 'name_version' => $this->getVersion(), 'customer' => $this->getCustomer(), 'title' => $this->getTitle());
            $xml = $this->_getContent($this->_sUrl . 'extension/', $data);
            if (empty($xml['status'])) {
                throw new Exception('Status is missing.', 1);
            }
            $status = $xml['status'];
        } catch (Exception $e) {
            if ($this->_test) {
                echo $e->getMessage();
                exit;
            }
            $status = $this->getSimpleStatus();
        }
        return $this->saveStatus($status);
    }
    protected function _getContent($u, $data = array())
    {
        $data['v'] = self::V;
        $query = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $u);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        $res = json_decode($res, true);
        if (!empty($res['cache_time']) && ($ct = (int) $res['cache_time']) && $ct > 0) {
            $this->_dbCacheTime = $ct;
        }
        return $res;
    }
    public function setDbCacheTime($ct)
    {
        $this->_dbCacheTime = $ct;
        return $this;
    }
    public function getSimpleStatus()
    {
        $session = $this->getSession();
        return strlen($session) == 32 && $session[9] == $this->_name[2] && (strlen($this->_name) < 4 || $session[20] == $this->_name[3]) ? 500 : 201;
    }
    public function getTitle()
    {
        return (string) Mage::getConfig()->getNode('modules/' . self::PR . $this->_name)->name;
    }
    public function saveStatus($status)
    {
        $this->_initInstall();
        $signature = $this->getSignature();
        Mage::getSingleton('core/resource')->getConnection('core_write')->query(sprintf("DELETE FROM %s WHERE `date` < '%s'", Mage::getSingleton('core/resource')->getTableName('plumbase_product'), date('Y-m-d H:i:s', time() - 2592000)));
        if (!$this->getId()) {
            $product = Mage::getModel('plumbase/product')->load($signature, 'signature');
            $this->setId($product->getId());
        }
        return $this->setSignature($signature)->setStatus($status)->setDate(date('Y-m-d H:i:s', time() + $this->_dbCacheTime * 86400))->save();
    }
    public function getVersion()
    {
        return (string) Mage::getConfig()->getNode('modules/' . self::PR . $this->_name)->version;
    }
    public function disable()
    {
        $helper = $this->getHelper();
        if (method_exists($helper, 'disableExtension')) {
            $helper->disableExtension();
        }
        return $this;
    }
    public function getAllModules()
    {
        $modules = (array) Mage::getConfig()->getNode('modules')->children();
        $result = array();
        $ad = 'advanced/modules_disable_output';
        foreach ($modules as $key => $module) {
            if (strpos($key, 'Plumrocket_') !== false && $module->is('active') && !Mage::getStoreConfig($ad . '/' . $key)) {
                $result[$key] = $module;
            }
        }
        return $result;
    }
    public function reindex()
    {
        $ck = self::PR . 'base_reindex';
        if (!Mage::getSingleton('admin/session')->isLoggedIn() || 86400 + Mage::app()->loadCache($ck) > time()) {
            if (!$this->_test) {
                return $this;
            }
        }
        $this->_initInstall();
        $data = array('edition' => self::getEdit(), 'products' => array(), 'base_urls' => $this->getBaseU());
        $products = array();
        foreach ($this->getAllModules() as $key => $module) {
            $name = str_replace(self::PR, '', $key);
            $product = Mage::getModel('plumbase/product')->load($name);
            if (!$product->enabled() || $product->isCached()) {
                continue;
            }
            $products[$name] = $product;
            $v = $product->getVersion();
            $c = $product->getCustomer();
            $s = $product->getSession();
            $data['products'][$name] = array($name, $v, $c ? $c : 0, $s ? $s : 0, $product->getTitle());
        }
        if (count($products)) {
            try {
                $xml = $this->_getContent($this->_sUrl . 'extensions/', $data);
                if (!isset($xml['statuses'])) {
                    throw new Exception('Statuses are missing.', 1);
                }
                $statuses = $xml['statuses'];
            } catch (Exception $e) {
                if ($this->_test) {
                    echo $e->getMessage();
                    exit;
                }
                $statuses = array();
                foreach ($products as $name => $product) {
                    $statuses[$name] = $product->getSimpleStatus();
                }
            }
            foreach ($products as $name => $product) {
                $status = isset($statuses[$name]) ? $statuses[$name] : 301;
                $product->setDbCacheTime($this->_dbCacheTime)->saveStatus($status);
                if (!$product->isInStock()) {
                    $product->disable();
                }
            }
        }
        Mage::app()->saveCache(time(), $ck);
    }
    protected function _initInstall()
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        if ($connection->isTableExists($resource->getTableName('plumbase/product'))) {
            return;
        }
        $file = "/var/wwwDSsqlDSbase_setupDSmysql4-upgrade-1.0.2-1.0.3.php";
        if (file_exists($file)) {
            include $file;
        }
    }
}
