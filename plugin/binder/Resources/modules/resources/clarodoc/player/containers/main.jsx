import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'

import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourcesSelectors} from '#/main/core/resource/store'

import {DocumentPlayerMain as DocumentPlayerMainComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/player/components/main'
import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store'

const DocumentPlayerMain = withRouter(
  connect(
    (state) => ({
      clarodoc: formSelect.originalData(formSelect.form(state, selectors.FORM_NAME)),
      currentContext: {
        type:"workspace",
        data:resourcesSelectors.workspace(state)
      }
    })
  )(DocumentPlayerMainComponent)
)

export {
  DocumentPlayerMain
}
