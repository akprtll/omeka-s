<?php
namespace Omeka\Media\Handler;

use Zend\Db\Sql\Ddl\Column\Text;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractHandler;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;
use Zend\Form\Element\Textarea;

class HtmlHandler extends AbstractHandler implements MutableHandlerInterface
{

    public function updateForm(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        $view->headscript()->appendFile($view->assetUrl('js/ckeditor/ckeditor.js', 'Omeka'));
        $view->headscript()->appendFile($view->assetUrl('js/ckeditor/adapters/jquery.js', 'Omeka'));
        $js = "
            $(document).ready(function() {
                $('textarea.media-html').ckeditor({'customConfig' : '" . $view->assetUrl('js/ckeditor_config.js', 'Omeka') . "'});
            });
        ";
        $view->headscript()->appendScript($js);
        $textarea = new Textarea('o:media[__index__][html]');
        $textarea->setOptions(array(
            'label' => $view->translate('HTML'),
            'info'  => $view->translate('HTML or plain text.'),
        ));
        $data = $media->mediaData();
        $html = $data['html'];
        $textarea->setAttributes(
                array(
                    'rows'     => 15,
                    'id'       => 'media-html-__index__',
                    'required' => true,
                    'class'    => 'media-html',
                    'value' => $html
                ));
        $field = $view->formField($textarea);
        return $field;
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {
        $view->headscript()->appendFile($view->assetUrl('js/ckeditor/ckeditor.js', 'Omeka'));
        $view->headscript()->appendFile($view->assetUrl('js/ckeditor/adapters/jquery.js', 'Omeka'));
        $textarea = new Textarea('o:media[__index__][html]');
        $textarea->setOptions(array(
            'label' => $view->translate('HTML'),
            'info'  => $view->translate('HTML or plain text.'),
        ));

        $textarea->setAttributes(
                array(
                    'rows'     => 15,
                    'id'       => 'media-html-__index__',
                    'required' => true,
                    'class'    => 'media-html'
                ));
        $field = $view->formField($textarea);
        $html = $field . "
            <script type='text/javascript'>
                    $('#media-html-__index__').ckeditor({'customConfig' : '" . $view->assetUrl('js/ckeditor_config.js', 'Omeka') . "'});
            </script>
        ";
        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('HTML');
    }

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['html'])) {
            $html = $data['html'];
            $serviceLocator = $this->getServiceLocator();
            $purifier = $serviceLocator->get('Omeka\HtmlPurifier');
            $html = $purifier->purify($html);
            $media->setData(array('html' => $html));
        }
    }

    public function update(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $html = $data['o:media']['__index__']['html'];
        $serviceLocator = $this->getServiceLocator();
        $purifier = $serviceLocator->get('Omeka\HtmlPurifier');
        $html = $purifier->purify($html);
        $media->setData(array('html' => $html));
    }

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        $data = $media->mediaData();
        return $data['html'];
    }
}