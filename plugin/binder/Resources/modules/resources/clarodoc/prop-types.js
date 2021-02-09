import {PropTypes as T} from 'prop-types'

import {Widget} from '#/main/core/widget/prop-types'

const Document = {
  propTypes: {
    id: T.string.isRequired,
    resourceName: T.string.isRequired,
    longTitle: T.string,
    centerTitle: T.bool.isRequired,
    widgets: T.arrayOf(T.shape(
      Widget.propTypes
    )),
    translations: T.arrayOf(T.object)
  },
  defaultProps: {
    widgets: [],
    centerTitle: false,
  }
}

export {
  Document
}
