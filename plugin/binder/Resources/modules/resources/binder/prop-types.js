import {PropTypes as T} from 'prop-types'
import {Document} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/prop-types'


const Binder = {
  propTypes: {
    id: T.string.isRequired,
    title: T.string
  }
}

const Tab = {
  propTypes: {
    id: T.string,
    title: T.string,
    slug:T.string,
    display: T.shape({
      visible:T.boolean,
      backgroundColor:T.string,
      position:T.number,
      borderColor:T.string,
      textColor:T.string,
      icon:T.string
    }),
    metadata: T.shape({
      details:T.oneOf([T.arrayOf(T.object),T.object,T.string]),
      type:T.oneOf(['document','binder','undefined']),
      roles: T.arrayOf(T.shape({
        id: T.string.isRequired,
        name: T.string.isRequired,
        translationKey: T.string.isRequired,
        type: T.number.isRequired
      }))
    }),
    resourceNode: T.object,
    content: T.oneOf([
        T.shape(Binder.propTypes),
        T.shape(Document.propTypes)
      ])
  },
  defaultProps: {
    display:{
      position:-1,
      backgroundColor:"white",
      borderColor:"white",
      textColor:"black",
    },
    metadata:{
      type:'undefined',
      roles:[]
    },
    content:undefined
  }
}

Binder.propTypes.tabs = T.arrayOf(T.shape(Tab.propTypes));
Binder.defaultProps =  {
  tabs: []
}



export {
  Binder,
  Tab
}
