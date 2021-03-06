<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\ContentModel;
use Contao\FormModel;
use Contao\FormFieldModel;
use Contao\ContentElement;
use Contao\Controller;

/**
 * ApiContentElement augments ContentModel for the API.
 *
 * @param int    $id       id of the ContentModel
 * @param string $inColumn In which column does the Content Element reside in
 */
class ApiContentElement extends AugmentedContaoModel
{
    public function __construct($id, $inColumn = 'main')
    {
        $this->model = ContentModel::findById($id, ['published'], ['1']);
        if (!Controller::isVisibleElement($this->model)) {
            return $this->model = null;
        }
        if ($this->type === 'module') {
            $contentModuleClass = ContentElement::findClass($this->type);
            $element = new $contentModuleClass($this->model, $inColumn);
            $this->subModule = new ApiModule($element->module);
        }
        if ($this->type === 'form') {
            $formModel = FormModel::findById($this->form);
            if ($formModel) {
                $formModel->fields = FormFieldModel::findPublishedByPid($formModel->id);
            }
            $this->subForm = $formModel;
        }
    }

    /**
     * Select by Parent ID and Table.
     *
     * @param int    $pid      Parent ID
     * @param string $table    Parent table
     * @param string $inColumn In which column doe the Content Elements reside in
     */
    public static function findByPidAndTable($pid, $table = 'tl_article', $inColumn = 'main')
    {
        $contents = [];
        foreach (ContentModel::findPublishedByPidAndTable($pid, $table, ['order' => 'sorting ASC']) as $content) {
            if (!Controller::isVisibleElement($content)) {
                continue;
            }
            $contents[] = new self($content->id, $inColumn);
        }

        return $contents;
    }

    /**
     * Does this Content Element have a reader module?
     *
     * @param string $readerType What kind of reader? e.g. 'newsreader'
     */
    public function hasReader($readerType): bool
    {
        return $this->subModule && $this->subModule->type == $readerType;
    }
}
