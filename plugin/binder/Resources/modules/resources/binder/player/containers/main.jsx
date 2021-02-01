import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'

import {selectors as formSelect} from '#/main/app/content/form/store/selectors'
import {selectors as resourcesSelectors} from '#/main/core/resource/store'

import {BinderPlayerMain as BinderPlayerMainComponent} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/player/components/main'
import {selectors} from '~/sidpt/binder-bundle/plugin/binder/resources/binder/store'

const BinderPlayerMain = withRouter(
  connect(
    (state) => ({
      binder: formSelect.originalData(formSelect.form(state, selectors.FORM_NAME)),
      currentContext: {
        type:"workspace",
        data:resourcesSelectors.workspace(state)
      }
    })
  )(BinderPlayerMainComponent)
)

export {
  BinderPlayerMain
}
