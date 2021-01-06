import React, {Fragment} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'
import isEmpty from 'lodash/isEmpty'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'

import {WidgetGridEditor} from '#/main/core/widget/editor/components/grid'

import {selectors} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/store/selectors'
 
const DocumentEditorMain = props =>
    <Fragment>
      <FormData
          level={2}
          name={selectors.FORM_NAME}
          buttons={true}
          disabled={false}
          target={(clarodoc) => ['sidpt_document_update', {id: clarodoc.id}]}
          cancel={{
              type: LINK_BUTTON,
              target: props.path,
              exact: true
            }}
          sections={[
            {
              icon: 'fa fa-fw fa-plus',
              title: trans('general'),
              primary: true,
              fields: [
                {
                  name: 'longTitle',
                  type: 'string',
                  label: trans('title'),
                  required: true
                },{
                  name: 'centerTitle',
                  type: 'boolean',
                  label: trans('center_title')
                }
              ]
            }
          ]} >
        <WidgetGridEditor
            disabled={false}
            currentContext={props.currentContext}
            widgets={props.clarodoc.widgets}
            tabs={[]} // TODO empty for now, is used for crosstab widget moves
            currentTabIndex={0}
            update={(widgets) => {
              props.update('widgets', widgets)}
            } />
      </FormData>
    </Fragment>;


DocumentEditorMain.propTypes = {
  path: T.string.isRequired,
  clarodoc: T.object.isRequired,
  currentContext: T.object.isRequired,
  update: T.func.isRequired
}

export {
  DocumentEditorMain
}

/**
 * 
 */