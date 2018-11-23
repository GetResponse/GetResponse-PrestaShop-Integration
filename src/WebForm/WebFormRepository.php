<?php

namespace GetResponse\WebForm;

use Configuration;
use GetResponse\Config\ConfigurationKeys;

/**
 * Class WebFormRepository
 */
class WebFormRepository
{
    /**
     * @param WebForm $webForm
     */
    public function update(WebForm $webForm)
    {
        Configuration::updateValue(
            ConfigurationKeys::WEB_FORM,
            json_encode([
                'status' => $webForm->getStatus(),
                'webform_id' => $webForm->getId(),
                'sidebar' => $webForm->getSidebar(),
                'style' => $webForm->getStyle(),
                'url' => $webForm->getUrl()
            ])
        );
    }

    /**
     * @return WebForm|null
     */
    public function getWebForm()
    {
        $result = json_decode(Configuration::get(ConfigurationKeys::WEB_FORM), true);

        if (empty($result)) {
            return WebForm::createEmptyInstance();
        }

        return new WebForm(
            $result['status'],
            $result['webform_id'],
            $result['sidebar'],
            $result['style'],
            $result['url']
        );
    }
}
