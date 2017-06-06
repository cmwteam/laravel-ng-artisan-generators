<?php

namespace LaravelAngular\Generators\Console\Commands;

use File;
use Illuminate\Console\Command;

class AngularComponent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ng:component {name} {custom_folder_name}
    {--no-spec : Don\'t create a test file}
    {--no-import : Don\'t auto import in index.components}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new component in angular/components';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        if ($this->argument('custom_folder_name') != 'null') {
            $custom_folder_name = $this->argument('custom_folder_name'); // ankit
        } else {
            $custom_folder_name = '';
        }
        $studly_name  = studly_case($name);
        $ng_component = str_replace('_', '-', $name);

        $html = file_get_contents(__DIR__ . '/Stubs/AngularComponent/component.html.stub');
        if ($this->argument('custom_folder_name') != 'null') {

            $js = file_get_contents(__DIR__ . '/Stubs/AngularComponent/custom-component.js.stub');
        } else {
            $js = file_get_contents(__DIR__ . '/Stubs/AngularComponent/component.js.stub');
        }
        $style = file_get_contents(__DIR__ . '/Stubs/AngularComponent/component.style.stub');
        $spec  = file_get_contents(__DIR__ . '/Stubs/AngularComponent/component.spec.js.stub');

        $js = str_replace('{{StudlyName}}', $studly_name, $js);
        $js = str_replace('{{name}}', $name, $js);
        $js = str_replace('{{custom_folder_name}}', $custom_folder_name, $js);

        $spec = str_replace('{{ng-component}}', $ng_component, $spec);
        if ($this->argument('custom_folder_name') != 'null') {
            $folder  = base_path(config('generators.source.root')) . '/' . config('generators.source.components') . '/' . $custom_folder_name . '/' . $name;
            $folder1 = base_path(config('generators.source.root')) . '/' . config('generators.source.components') . '/' . $name;
            if (is_dir($folder1) || is_dir($folder)) {
                $this->info('Folder already exists');
                return false;
            }
        } else {
            $folder  = base_path(config('generators.source.root')) . '/' . config('generators.source.components') . '/' . $name;
            $dir_arr = scandir(base_path(config('generators.source.root')) . '/' . config('generators.source.components'));

            foreach ($dir_arr as $key => $value) {
                if (is_dir(base_path(config('generators.source.root')) . '/' . config('generators.source.components') . '/' . $value)) {
                    $sub_dir = scandir(base_path(config('generators.source.root')) . '/' . config('generators.source.components') . '/' . $value);
                    if (in_array($name, $sub_dir)) {
                        $this->info('Folder already exists');
                        return false;
                    }
                }
            }
            if (is_dir($folder)) {
                $this->info('Folder already exists');
                return false;
            }
        }

        $spec_folder = base_path(config('generators.tests.source.root')) . '/' . config('generators.tests.source.components');

        //create folder
        File::makeDirectory($folder, 0775, true);
        //File::makeDirectory($folder, 0775, true);

        //create view (.component.html)
        File::put($folder . '/' . $name . config('generators.suffix.componentView'), $html);

        //create component (.component.js)
        File::put($folder . '/' . $name . config('generators.suffix.component'), $js);
        //create style file
        File::put($folder . '/' . $name . '.' . config('generators.suffix.stylesheet', 'scss'), $style);

        if (!$this->option('no-spec') && config('generators.tests.enable.components')) {
            //create spec file (.component.spec.js)
            File::put($spec_folder . '/' . $name . '.component.spec.js', $spec);
        }

        //import component
        $components_index = base_path(config('generators.source.root')) . '/index.components.js';
        if (config('generators.misc.auto_import') && !$this->option('no-import') && file_exists($components_index)) {
            $components    = file_get_contents($components_index);
            $componentName = lcfirst($studly_name);
            $newComponent  = "\r\n\t.component('$componentName', {$studly_name}Component)";
            $module        = "angular.module('app.components')";
            $components    = str_replace($module, $module . $newComponent, $components);
            if ($this->argument('custom_folder_name') != 'null') {
                $components = 'import {' . $studly_name . "Component} from './app/components/{$custom_folder_name}/{$name}/{$name}.component';\n" . $components;
            } else {
                $components = 'import {' . $studly_name . "Component} from './app/components/{$name}/{$name}.component';\n" . $components;
            }
            file_put_contents($components_index, $components);
        }

        $this->info('Component created successfully.');
    }
}
