import {PropTypes as T, implementPropTypes} from '#/main/app/prop-types'

import {ListParameters} from '#/main/app/content/list/prop-types'
import {WidgetInstance} from '#/main/core/widget/content/prop-types'

const ResourceSearchWidgetParameters = implementPropTypes({}, ListParameters, {
  searchFormConfiguration: T.object,
  maxResults: T.number
})

const ResourceSearchWidget = implementPropTypes({}, WidgetInstance, {
  parameters: ResourceSearchWidgetParameters.propTypes
}, {
  parameters: ResourceSearchWidgetParameters.defaultProps
})

export {
  ResourceSearchWidget,
  ResourceSearchWidgetParameters
}
