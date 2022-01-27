
import {BinderResource} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/containers/resource'
import {BinderMenu} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/containers/menu'

import {reducer} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder/store'

/**
 * HomeTool application.
 */
export default {
  component: BinderResource,
  menu: BinderMenu,
  store: reducer,
  styles: ['sidpt-binder-plugin-binder-binder-resource']
}
