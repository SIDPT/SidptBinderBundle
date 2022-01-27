import {connect} from 'react-redux'

import {withRouter} from '#/main/app/router'
import {selectors as formSelect} from '#/main/app/content/form/store/selectors'

import {DocumentMenu as DocumentMenuComponent} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/components/menu'
import {selectors} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/store'

const DocumentMenu = withRouter(
  connect(
    (state) => ({
      clarodoc: formSelect.originalData(formSelect.form(state, selectors.STORE_NAME+'.clarodoc'))
    })
  )(DocumentMenuComponent)
)

export {
  DocumentMenu
}
