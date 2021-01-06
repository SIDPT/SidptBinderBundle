import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {selectors as toolSelectors} from '#/main/core/tool/store'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'

import {DocumentPlayerMain as DocumentPlayerMainComponent} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/player/components/main'
import {selectors} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/store'

const DocumentPlayerMain = withRouter(
  connect(
    (state) => ({
      clarodoc: formSelect.originalData(formSelect.form(state, selectors.FORM_NAME)),
      currentContext: toolSelectors.context(state)
    })
  )(DocumentPlayerMainComponent)
)

export {
  DocumentPlayerMain
}
