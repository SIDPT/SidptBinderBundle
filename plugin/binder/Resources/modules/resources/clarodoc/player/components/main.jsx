import React,{Fragment} from 'react'
import {PropTypes as T} from 'prop-types'

import {PageTitle} from '#/main/app/page/components/header'

import {WidgetGrid} from '#/main/core/widget/player/components/grid'
import {WidgetContainer as WidgetContainerTypes} from '#/main/core/widget/prop-types'

const DocumentPlayerMain = props =>
  <Fragment>
    <header className={props.clarodoc.centerTitle ? "text-center" : ''}> 
      <h1 className="page-title">{props.clarodoc.longTitle}</h1>
    </header>
    <WidgetGrid 
        currentContent={props.currentContext} 
        widgets={props.clarodoc.widgets}
    />
  </Fragment>

DocumentPlayerMain.propTypes = {
  clarodoc:T.object.isRequired,
  currentContext:T.object.isRequired
}

export {
  DocumentPlayerMain
}

/**
 * 
 */