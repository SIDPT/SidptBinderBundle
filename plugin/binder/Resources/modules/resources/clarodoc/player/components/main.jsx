import React,{Fragment, Component} from 'react'
import {PropTypes as T} from 'prop-types'

import {Translator, trans} from '#/main/app/intl/translation'

import {ContentPlaceholder} from '#/main/app/content/components/placeholder'
import {Widget} from '#/main/core/widget/player/components/widget'


class DocumentPlayerMain extends Component {
  constructor(props) {
    super(props);
    const translations = this.props.clarodoc.translations;
    if(translations.length > 0){
      for(const field of translations){
        for(const locale in field.locales){
          if(field.locales[locale].length > 0){
            Translator.add(
              field.path,
              field.locales[locale],
              `${this.props.clarodoc.id}`,
              locale);
          }
        }
      }
    }

  }
  render(){
    let visibleWidgets = this.props.clarodoc.widgets.filter(
        widget => widget.visible === true
      ).map((widget,index)=>{
        let temp = Object.assign({}, widget);
        let existingTranslation = trans(`widgets[${index}].name`,{},`${this.props.clarodoc.id}`);
        if(existingTranslation && 
              existingTranslation.length > 0 && 
              existingTranslation !== `widgets[${index}].name`){
          temp.name = existingTranslation;
        }
        return temp;
      });

      return (
        <Fragment>
          <header className={this.props.clarodoc.centerTitle ? "text-center" : ''}> 
            <h1 className="page-title">{this.props.clarodoc.longTitle}</h1>
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
                  currentContext={this.props.currentContext}
                />
              )}
            </div>
          }
        </Fragment>
        );
  }
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