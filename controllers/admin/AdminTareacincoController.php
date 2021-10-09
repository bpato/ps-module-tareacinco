<?php
/**
 * Copyright (C) 2020 Brais Pato
 *
 * NOTICE OF LICENSE
 *
 * This file is part of Simplerecaptcha <https://github.com/bpato/simplerecaptcha.git>.
 * 
 * Simplerecaptcha is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Simplerecaptcha is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Foobar. If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    Brais Pato <patodevelop@gmail.com>
 * @copyright 2020 Brais Pato
 * @license   https://www.gnu.org/licenses/ GNU GPLv3
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminTareacincoController extends \ModuleAdminController
{
    /** @var Tareados $module */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'configuration';
        $this->className = 'Configuration';

        parent::__construct();

        $this->fields_options = [];
        $this->fields_options[0] = [
            'title' => $this->trans('Modulo Tarea cinco', [], 'Modules.Tareacinco.Admin'),
            'icon' => 'icon-cogs',
            'fields' => [
                [
                    'title' => $this->trans('Frase', [], 'Modules.Tareacinco.Admin'),
                    'desc' => $this->trans('Frase que se mostrara en este producto.', [], 'Modules.Tareacinco.Admin'),
                    'lang' => true,
                    'type' => 'textareaLang',
                    'validation' => 'isCleanHtml',
                ],
            ],
            'submit' => ['title' => $this->trans('Save', [], 'Admin.Actions')],
        ];
    }
}