<?php
/**
 * copyright 2013      Stephen Just <stephenjust@users.sourceforge.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

header("Content-Type: application/json");
if (empty($_GET["data-type"]))
{
    exit_json_error("data-type param is not defined or is empty");
}

switch ($_GET["data-type"])
{
    case "addon";
        if (Validate::ensureNotEmpty($_GET, ["addon-type", "query", "flags"]))
        {
            exit_json_error("One or more fields are empty. This should never happen");
        }

        $return_html = isset($_GET["return-html"]) ? true : false;
        $addons = [];
        try
        {
            $addons = Addon::search($_GET["query"], $_GET["addon-type"], $_GET["flags"]);
        }
        catch(AddonException $e)
        {
            exit_json_error($e->getMessage());
        }

        $template_addons = Addon::filterMenuTemplate($addons);
        if ($return_html)
        {
            $addons_html = StkTemplate::get("addons/menu.tpl")
                ->assign("addons", $template_addons)
                ->assign("pagination", "")
                ->toString();
            exit_json_success("", ["addons-html" => $addons_html]);
        }

        exit_json_success("", ["addons" => $template_addons]);
        break;

    case "bug":
        if (Validate::ensureNotEmpty($_GET, ["search-filter"]))
        {
            exit_json_error("One or more fields are empty. This should never happen");
        }
        if (!isset($_GET["query"])) // set as empty, will be handled by Bug::search
        {
            $_GET["query"] = "";
        }

        // search also the description
        $search_description = Util::isCheckboxChecked($_GET, "search-description");

        $bugs = [];
        try
        {
            $bugs = Bug::search($_GET["query"], $_GET["search-filter"], $search_description);
        }
        catch(BugException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success("", ["bugs-all" => StkTemplate::get('bugs/all.tpl')->assign("bugs", ["items" => $bugs])->toString()]);
        break;

    case "user":
        if (!User::isLoggedIn())
        {
            exit_json_error("You are not logged in");
        }
        if (Validate::ensureNotEmpty($_GET, ["query"]))
        {
            exit_json_error("One or more fields are empty. This should never happen");
        }

        $return_html = isset($_GET["return-html"]) ? true : false;
        $users = [];
        try
        {
            $users = User::search($_GET["query"]);
        }
        catch(BugException $e)
        {
            exit_json_error($e->getMessage());
        }

        $template_users = User::filterMenuTemplate($users);
        if ($return_html)
        {
            $users_html = StkTemplate::get("users/menu.tpl")
                ->assign("img_location", IMG_LOCATION)
                ->assign("users", $template_users)
                ->assign("pagination", "")
                ->toString();
            exit_json_success("", ["users-html" => $users_html]);
        }

        exit_json_success("", ["users" => $template_users]);
        break;

    default:
        exit_json_error(sprintf("data_type = %s is not recognized", h($_POST["action"])));
        break;
}
