import React, {Component} from 'react'
import {connect} from 'react-redux'
import isEqual from 'lodash/isEqual'

import {ResourcesSearchWidget as ResourcesSearchWidgetComponent} from '~/sidpt/binder-bundle/plugin/binder/widgets/resources_search/components/widget'

import {withReducer} from '#/main/app/store/components/withReducer'

import {makeListWidgetReducer, selectors} from '#/main/core/widget/types/list/store'

import {actions as listActions} from '#/main/app/content/list/store/actions'

import {selectors as contentSelectors} from '#/main/core/widget/content/store'

class Widget extends Component {
  shouldComponentUpdate(nextProps) {
    return !isEqual(nextProps, this.props)
  }

  render() {
    const ListWidgetInstance = withReducer(
      selectors.STORE_NAME,
      makeListWidgetReducer(selectors.STORE_NAME, {
        pagination: {pageSize: this.props.pageSize},
        filters: this.props.filters,
        sortBy: this.props.sorting
      })
    )(ResourcesSearchWidgetComponent)

    return (
      <ListWidgetInstance {...this.props} />
    )
  }
}

const ResourcesSearchWidget = connect(
  (state) => ({
    source: contentSelectors.source(state),
    currentContext: contentSelectors.context(state),

    parameters: contentSelectors.parameters(state),


    // list configuration
    pageSize: selectors.pageSize(state),
    filters: selectors.filters(state),
    sorting: selectors.sorting(state)
  }),
  (dispatch) => ({
    setFilters(newFilters = []){
      dispatch(listActions.resetFilters(selectors.STORE_NAME, newFilters))
      dispatch(listActions.invalidateData(selectors.STORE_NAME))
    }
  })
)(Widget)

export {
  ResourcesSearchWidget
}
