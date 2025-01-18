import firebase from 'firebase/compat/app';

// Add the Firebase products that you want to use
import "firebase/compat/auth";
import "firebase/compat/firestore";

export class FirebaseAuthBackend {
  constructor(firebaseConfig) {
    if (firebaseConfig) {
      // Initialize Firebase
      debugger
      firebase.initializeApp(firebaseConfig);
      firebase.auth().onAuthStateChanged(user => {
        if (user) {
          localStorage.setItem("authUser", JSON.stringify(user));
        } else {
          localStorage.removeItem("authUser");
        }
      });
    }
  }

  /**
   * Registers the user with given details
   */
  registerUser = (email, password) => {
    return new Promise((resolve, reject) => {
      firebase
        .auth()
        .createUserWithEmailAndPassword(email, password)
        .then(
          user => {
            resolve(firebase.auth().currentUser);
          },
          error => {
            reject(this._handleError(error));
          }
        );
    });
  };

  /**
   * Registers the user with given details
   */
  editProfileAPI = (email, password) => {
    return new Promise((resolve, reject) => {
      firebase
        .auth()
        .createUserWithEmailAndPassword(email, password)
        .then(
          user => {
            resolve(firebase.auth().currentUser);
          },
          error => {
            reject(this._handleError(error));
          }
        );
    });
  };

  /**
   * Login user with given details
   */
  loginUser = (email, password) => {
    debugger
    return new Promise((resolve, reject) => {
      firebase
        .auth()
        .signInWithEmailAndPassword(email, password)
        .then(
          user => {
            
            return resolve(firebase.auth().currentUser);
          },
          error => {
            let er = error
            console.log
            
            reject(this._handleError(error));
          }
        );
    });
  };

  /**
   * forget Password user with given details
   */
  forgetPassword = email => {
    return new Promise((resolve, reject) => {
      firebase
        .auth()
        .sendPasswordResetEmail(email, {
          url:
            window.location.protocol + "//" + window.location.host + "/login",
        })
        .then(() => {
          resolve(true);
        })
        .catch(error => {
          reject(this._handleError(error));
        });
    });
  };

  /**
   * Logout the user
   */
  logout = () => {
    return new Promise((resolve, reject) => {
      firebase
        .auth()
        .signOut()
        .then(() => {
          resolve(true);
        })
        .catch(error => {
          reject(this._handleError(error));
        });
    });
  };

  /**
  * Social Login user with given details
  */

  socialLoginUser = async (type) => {
    let provider;
    if (type === "google") {
      provider = new firebase.auth.GoogleAuthProvider();
    } else if (type === "facebook") {
      provider = new firebase.auth.FacebookAuthProvider();
    }
    try {
      const result = await firebase.auth().signInWithPopup(provider);
      const user = result.user;
      return user;
    } catch (error) {
      throw this._handleError(error);
    }
  };

  addNewUserToFirestore = (user) => {
    const collection = firebase.firestore().collection("users");
    const { profile } = user.additionalUserInfo;
    const details = {
      firstName: profile.given_name ? profile.given_name : profile.first_name,
      lastName: profile.family_name ? profile.family_name : profile.last_name,
      fullName: profile.name,
      email: profile.email,
      picture: profile.picture,
      createdDtm: firebase.firestore.FieldValue.serverTimestamp(),
      lastLoginTime: firebase.firestore.FieldValue.serverTimestamp(),
      isAdmin: false,
    };
    collection.doc(firebase.auth().currentUser.uid).set(details);
    return { user, details };
  };

  getDocByProp = async (collectionName, propName, propValue) => {
    let result = null
    try {

      const db = firebase.firestore();
      const querySnapshot = await db.collection(collectionName)
        .where(propName, '==', propValue)
        .get()

      querySnapshot.forEach((doc) => {
        result = doc.data()
      });

      return result
    } catch (error) {
      console.log(error.message)
      return result
    }
  };

  getCollection = async (collectionName) => {

    let result = []
    try {

      const queryGet = query(collection(db, collectionName), orderBy("number"));
      const querySnapshot = await getDocs(queryGet);

      querySnapshot.forEach((doc) => {
        let data = doc.data()

        result.push(data)
      });

      return result
    } catch (error) {

      console.log(error)
    }

    return result
  };

  setCollection = async (collectionName, objects) => {

    try {


      let docRef = null
      const chunks = makeChunks(objects, 500);
      for (let i = 0; i < chunks.length; i++) {

        const batch = writeBatch(db)
        chunks[i].forEach((element) => {
          let number = element.number ? `${element.number}` : null
          docRef = docRef = doc(db, collectionName, `${element.number}`);
          batch.set(docRef, element, `${element.number}`)

        });

        await batch.commit();
      }
      let result = await generalAPI.getCollection(collectionName)

      return result


    } catch (error) {

      console.error(error)
    }
  };

  setCollectionUniqueId = async (collectionName, objects) => {

    try {


      let docRef = null
      const chunks = makeChunks(objects, 500);
      for (let i = 0; i < chunks.length; i++) {

        const batch = writeBatch(db)
        chunks[i].forEach((element) => {
          // Получите ссылку на коллекцию
          const collectionRef = collection(db, collectionName);
          // Создайте новую ссылку на документ в этой коллекции без указания ID
          const docRef = doc(collectionRef);
          // Используйте эту ссылку в пакетной записи
          batch.set(docRef, element);

        });
        await batch.commit();
      }
      let result = await generalAPI.getCollection(collectionName)

      return result


    } catch (error) {

      console.error(error)
    }
  };

  setLoggeedInUser = user => {
    localStorage.setItem("authUser", JSON.stringify(user));
  };

  /**
   * Returns the authenticated user
   */
  getAuthenticatedUser = () => {
    if (!localStorage.getItem("authUser")) return null;
    return JSON.parse(localStorage.getItem("authUser"));
  };

  /**
   * Handle the error
   * @param {*} error
   */
  _handleError(error) {
    // var errorCode = error.code;
    var errorMessage = error.message;
    return errorMessage;
  }
}

let _fireBaseBackend = null;

/**
 * Initilize the backend
 * @param {*} config
 */
const initFirebaseBackend = config => {
  if (!_fireBaseBackend) {
    _fireBaseBackend = new FirebaseAuthBackend(config);
  }
  return _fireBaseBackend;
};

/**
 * Returns the firebase backend
 */
const getFirebaseBackend = () => {
  return _fireBaseBackend;
};

export { initFirebaseBackend, getFirebaseBackend };
