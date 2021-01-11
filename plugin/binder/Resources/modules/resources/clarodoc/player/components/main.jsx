import React,{Fragment} from 'react'
import {PropTypes as T} from 'prop-types'

import {trans} from '#/main/app/intl/translation'
import {ContentPlaceholder} from '#/main/app/content/components/placeholder'
import {Widget} from '#/main/core/widget/player/components/widget'

const DocumentPlayerMain = props => { 
  let visibleWidgets = props.clarodoc.widgets.filter(
      widget => widget.visible === true
  );

  return (
    <Fragment>
      <header className={props.clarodoc.centerTitle ? "text-center" : ''}> 
        <h1 className="page-title">{props.clarodoc.longTitle}</h1>
      </header>
      {0 === visibleWidgets.length &&
          <ContentPlaceholder
            size="lg"
            icon="fa fa-frown-o"
            title={trans('no_section')}
          />
        }

      {0 !== visibleWidgets.length &&
        <div className="widgets-grid">
          {visibleWidgets.map((widget, index) =>
            <Widget
              key={index}
              widget={widget}
              currentContext={props.currentContext}
            />
          )}
        </div>
      }
    </Fragment>
    );
}
  

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