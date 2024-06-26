<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace theme_horizon\output;
use context_module;
use moodle_url;
use html_writer;
use stdClass;
use single_button;
use folder_tree;
/**
 * Class mod_folder_renderer
 *
 * @package    theme_horizon
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_folder_renderer extends \plugin_renderer_base {

    /**
     * Returns html to display the content of mod_folder
     * (Description, folder files and optionally Edit button)
     *
     * @param stdClass $folder record from 'folder' table (please note
     *     it may not contain fields 'revision' and 'timemodified')
     * @return string
     */
    public function display_folder(stdClass $folder) {

        $output = '';
        $folderinstances = get_fast_modinfo($folder->course)->get_instances_of('folder');
        if (!isset($folderinstances[$folder->id]) ||
                !($cm = $folderinstances[$folder->id]) ||
                !($context = context_module::instance($cm->id))) {
            // Some error in parameters.
            // Don't throw any errors in renderer, just return empty string.
            // Capability to view module must be checked before calling renderer.
            return $output;
        }

        if (trim($folder->intro)) {
            if ($folder->display == FOLDER_DISPLAY_INLINE && $cm->showdescription) {
                // for "display inline" do not filter, filters run at display time.
                $output .= format_module_intro('folder', $folder, $cm->id, false);
            }
        }
        $buttons = '';
        // Display the "Edit" button if current user can edit folder contents.
        // Do not display it on the course page for the teachers because there
        // is an "Edit settings" button right next to it with the same functionality.
        $canmanagefolderfiles = has_capability('mod/folder:managefiles', $context);
        $canmanagecourseactivities = has_capability('moodle/course:manageactivities', $context);
        if ($canmanagefolderfiles && ($folder->display != FOLDER_DISPLAY_INLINE || !$canmanagecourseactivities)) {
            $editbutton = new single_button(new moodle_url('/mod/folder/edit.php', ['id' => $cm->id]),
                get_string('edit'), 'post', single_button::BUTTON_PRIMARY);
            $editbutton->class = 'navitem';
            $buttons .= $this->render($editbutton);
        }

        // Do not append the edit button on the course page.
        $downloadable = folder_archive_available($folder, $cm);
        if ($downloadable) {
            $downloadbutton = new single_button(new moodle_url('/mod/folder/download_folder.php', ['id' => $cm->id]),
                get_string('downloadfolder', 'folder'), 'get');
            $downloadbutton->class = 'navitem';
            $buttons .= $this->render($downloadbutton);
        }

        if ($buttons) {
            $output .= $this->output->container_start("container-fluid tertiary-navigation");
            $output .= $this->output->container_start("row");
            $output .= $buttons;
            $output .= $this->output->container_end();
            $output .= $this->output->container_end();
        }

        $foldertree = new folder_tree($folder, $cm);
        if ($folder->display == FOLDER_DISPLAY_INLINE) {
            // Display module name as the name of the root directory.
            $foldertree->dir['dirname'] = $cm->get_formatted_name(array('escape' => false));
        }
        $output .= $this->output->container_start("box generalbox pt-0 pb-3 foldertree");
        $output .= $this->render($foldertree);
        $output .= $this->output->container_end();

        return $output;
    }

    public function render_folder_tree(folder_tree $tree) {
        static $treecounter = 0;

        $content = '';
        $id = 'folder_tree'. ($treecounter++);
        $content .= '<div id="'.$id.'" class="filemanager">';
        $content .= $this->htmllize_tree($tree, array('files' => array(), 'subdirs' => array($tree->dir)));
        $content .= '</div>';
        $showexpanded = true;
        if (empty($tree->folder->showexpanded)) {
            $showexpanded = false;
        }
        // $this->page->requires->js_init_call('M.mod_folder.init_tree', array($id, $showexpanded));
        return $content;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG, $OUTPUT;

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        // $result = '<ul>';
        $result = '';
        foreach ($dir['subdirs'] as $subdir) {
            // $image = $this->output->pix_icon(file_folder_icon(24), $subdir['dirname'], 'moodle');
            // $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
            //         html_writer::tag('span', s($subdir['dirname']), array('class' => 'fp-filename'));
            // $filename = html_writer::tag('div', $filename, array('class' => 'fp-filename-icon'));
            // $result .= html_writer::tag('span', $filename. $this->htmllize_tree($tree, $subdir));
            $result .= $this->htmllize_tree($tree, $subdir);
        }
        foreach ($dir['files'] as $file) {

            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                    $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename, false);
            $filenamedisplay = clean_filename($filename);
            if (file_extension_in_typegroup($filename, 'web_image')) {
                $image = $url->out(false, array('preview' => 'tinyicon', 'oid' => $file->get_timemodified()));
                $image = html_writer::empty_tag('img', array('src' => $image));
            } else {
                $image = $this->pix_icon(file_file_icon($file, 24), $filenamedisplay, 'moodle');
            }
            $filename = 
                    html_writer::tag('span', $filenamedisplay, array('class' => 'fp-filename'));
            $urlparams = null;
            $urlparams = ['forcedownload' => 1];
            $imageicon = $this->image_url('folder', 'theme')->out();
            $downloadicon = $this->image_url('download', 'theme')->out();
            // print_object($OUTPUT->image_url('folder', 'theme'));
            // print_object($imageicon);
            // print_object($OUTPUT->image_url('package', 'local_packages'));
            $result .=  $filename = '<div class="folder_cards">
                            <div class="foldercard_header d-flex justify-content-between">
                                <h6 data-toggle="tooltip" title="'.$file->get_filename().'">'.$filename.'</h6>
                                <img src='.$imageicon.'>
                            </div>
                            <div class="download_btn">
                                <a href='.$url->out(false, $urlparams).'>Download <img class="downloadimage" src='.$downloadicon.'></a>
                            </div>
                        </div>';
            // $result .= html_writer::tag('span', $filename);
        }
        // $result .= '</ul>';
        return $result;
    }
}
