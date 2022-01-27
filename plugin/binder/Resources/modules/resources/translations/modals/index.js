import {registry} from '#/main/app/modals/registry'

import {TranslationsModal} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/translations/modals/components/translations'

const MODAL_TRANSLATIONS = 'MODAL_TRANSLATIONS'

registry.add(MODAL_TRANSLATIONS, TranslationsModal)

export {
  MODAL_TRANSLATIONS
}