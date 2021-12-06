import React, {Component, Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import {cloneDeep} from 'lodash/cloneDeep'
import {ListSource} from '#/main/app/content/list/containers/source'
import {getSource} from '#/main/app/data/sources'
import {ListParameters as ListParametersTypes} from '#/main/app/content/list/parameters/prop-types'

import {Checkboxes} from '#/main/app/input/components/checkboxes'


import {CALLBACK_BUTTON, CallbackButton} from '#/main/app/buttons'

import {trans} from '#/main/app/intl/translation'

import {selectors} from '#/main/core/widget/types/list/store'

// todo : implement actions

class ResourcesSearchWidget extends Component {
  constructor(props) {
    super(props)

    this.state = {
      source: undefined,
      showResult:false,
      lockedFilters:this.props.parameters.filters.filter(filter => filter.locked && filter.locked == true),
      userSelection:{}
    }
    this.displayResult = this.displayResult.bind(this)
    this.setUserSelection = this.setUserSelection.bind(this)
  }

  componentDidMount() {
    getSource('resources').then(module => this.setState({
      source: module.default
    }))
  }

  displayResult(showResult){
    this.setState({showResult:showResult})
  }

  setUserSelection(property,values){
    const selection = this.state.userSelection
    selection[property] = values
    this.setState({
      userSelection:selection
    })
  }

  render() {
    if (!this.state.source) {
      return null
    }
    const unlockedFilters = this.props.parameters.filters.filter(filter => !filter.locked)
    if(!this.state.showResult && unlockedFilters.length > 0){
      return(
        <Fragment>
          {unlockedFilters.map((filter, index) => {
            const customization = this.props.parameters.searchFormConfiguration[filter.property]
            // reprendre la customization des tables
            const formLabel = customization.label ?
              ( customization.translate ?
                trans(customization.label,{}, customization.transDomain || 'platform') :
                customization.label
              ) :
              trans('Select one or more values in the following list:', {}, 'widget')

            const hideLabel = false || customization.hideLabel
            return (
              <section key={`${filter.property}-${index}`}>
                  {!hideLabel && formLabel}
                  <Checkboxes
                    onChange={(values) => {
                      this.setUserSelection(filter.property, values)
                    }}
                    id={`select-${filter.property}`}
                    value={this.state.userSelection[filter.property]}
                    inline={false}
                    choices={
                      // convert filters array to an associative array
                      // { '{property}_{id}':'{name}'
                      filter.value.reduce(
                        (acc, subValue, index) => {
                          const accIndex = subValue.hasOwnProperty('id') ?
                            subValue.id : `${filter.property}_` + (
                              subValue.hasOwnProperty('name') ?
                                subValue.name : subValue.toString())
                          const accValue = subValue.hasOwnProperty('name') ?
                            subValue.name : subValue.hasOwnProperty('id') ?
                              subValue.id : subValue.toString()
                          acc[accIndex] = accValue
                          return acc;
                      },{})
                    }/>

              </section>)
          })}
          <CallbackButton
            className="btn btn-primary"
            label={trans('search')}
            callback={() => {
              const userFilter = []
              // compute filter from user selection
              for (const property in this.state.userSelection) {
                if (this.state.userSelection.hasOwnProperty(property)) {
                  var newFilter = unlockedFilters.find(
                    (filter) => filter.property === property
                  )

                  userFilter.push({
                    property:property,
                    locked:true,
                    value: newFilter.value.filter(
                      (subValue) => this.state.userSelection[property]
                        .findIndex(
                          (newValue) => newValue === (
                            subValue.hasOwnProperty('id') ?
                              subValue.id :
                              `${filter.property}_` + (
                                subValue.hasOwnProperty('name') ?
                                  subValue.name :
                                  subValue.toString()
                              )
                          )
                        ) >= 0
                      )
                  })
                }
              }
              for (var lockedFilter of this.state.lockedFilters) {
                userFilter.push(lockedFilter)
              }
              this.props.setFilters(userFilter)
              this.displayResult(true)
            }}>
            {trans('search')}
          </CallbackButton>
        </Fragment>
      )
    } else {

      return (
        <Fragment>
          {unlockedFilters.length > 0 &&
            <CallbackButton
              className="btn btn-primary"
              label={trans('reset_selection')}
              callback={() => {
                this.displayResult(false)
              }}>
              {trans('reset_selection')}
            </CallbackButton>
          }
          <ListSource
            name={selectors.STORE_NAME}
            fetch={{
              url: ['apiv2_data_source', {
                type: 'resources',
                context: this.props.currentContext.type,
                contextId: 'workspace' === this.props.currentContext.type ? this.props.currentContext.data.id : null,
                options:['translated']
              }],
              autoload: true
            }}
            source={this.state.source}
            parameters={this.props.parameters}
          />

        </Fragment>
      )
    }

  }
}

ResourcesSearchWidget.propTypes = {
  source: T.string,
  currentContext: T.object.isRequired,
  parameters: T.shape(
    ListParametersTypes.propTypes
  )
}

export {
  ResourcesSearchWidget
}
