import {createSelector} from 'reselect'

import {trans} from '#/main/app/intl/translation'
import {makeId} from '#/main/core/scaffolding/id'

import {selectors as toolSelectors} from '#/main/core/tool/store/selectors'
import {selectors as resourceSelect} from '#/main/core/resource/store'
import {hasPermission} from '#/main/app/security'

const STORE_NAME = 'sidpt_document'

const FORM_NAME = `${STORE_NAME}.clarodoc`

const resource = (state) => state[STORE_NAME]

export const selectors = {
  STORE_NAME,
  FORM_NAME,
  resource
}
