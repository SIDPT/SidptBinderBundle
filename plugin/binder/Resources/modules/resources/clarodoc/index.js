
import {DocumentResource} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/containers/resource'
import {DocumentMenu} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/containers/menu'

import {reducer} from '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc/store'

/**
 * HomeTool application.
 */
export default {
  component: DocumentResource,
  menu: DocumentMenu,
  store: reducer,
  styles: ['sidpt-binder-plugin-binder-document-resource']
}
