// import { initializeApp } from "firebase/app";
// import { firebaseConfig } from "../secret/secret";
// import { doc, getDoc, getFirestore, setDoc, updateDoc, where, writeBatch } from "firebase/firestore";
// import { collection, getDocs } from "firebase/firestore";
// import { query, orderBy } from "firebase/firestore";

// import { getAuth, GoogleAuthProvider, onAuthStateChanged, signInWithEmailAndPassword, signInWithPopup } from "firebase/auth";
// import { getFunctions, httpsCallable } from 'firebase/functions';
// import { api } from "../api-laravel";
// import { makeChunks } from "../../utils/firebase/firebase-utils";
// import { onlineAPI } from "../april-online-api/online-api";



// // TODO: Add SDKs for Firebase products that you want to use
// // https://firebase.google.com/docs/web/setup#available-libraries

// // Your web app's Firebase configuration
// // For Firebase JS SDK v7.20.0 and later, measurementId is optional



// //todo

// //сделать enum с названиями коллекций
// //TS must be here








// // Initialize Firebase
// export const app = initializeApp(firebaseConfig);
// // const analytics = getAnalytics(app);
// export const db = getFirestore(app);
// const functions = getFunctions(app);

// // export const auth = firebase.auth()
// export const firestore = getFirestore(app);
// // export const myFirebase = 

// export const clientAPI = {
//   // alfacentr.bitrix24.ru
//   create: async (name, email, domain, placementKey, hookKey, clientId, clientSecret) => {

//     console.log('name', 'email', 'domain', 'placementKey', 'hookKey')
//     console.log(name, email, domain, placementKey, hookKey, clientId, clientSecret)

//     try {
//       const addClient = httpsCallable(functions, 'setNewClient');
//       let client = await addClient({ name, email, domain, placementKey, hookKey, clientId, clientSecret })


//       let result
//       const resultClientsFetch = query(collection(db, "clients"), where("domain", "==", domain));
//       const resultClients = await getDocs(resultClientsFetch);


//       resultClients.forEach((doc) => {
//         if (doc.data().domain === domain) {
//           result = doc.data()
//         }
//       });

//       console.log(client.data)
//       if (result) {
//         let onlinePostPortal = await onlineAPI.setPortal(
//           result.domain,
//           result.key, //placement key 
//           result.clientId,
//           result.clientSecret,
//           result.hook //hook url
//         )

//       }






//       return client.data
//     } catch (error) {
//       console.log(error)
//       let message = 'client was not created'
//       alert(message)
//       if (error.message) {
//         message = error.message
//       }
//       let response = {
//         resultCode: 1,
//         message
//       }
//       return response
//     }

//   },

//   updateClient: async (client) => {

//     let result
//     let clientId = client && client.number
//     try {


//       const queryGet = query(collection(db, "clients"), where("number", "==", clientId));
//       const querySnapshot = await getDocs(queryGet);


//       let searchingClient = null //as FirebaseClientType | null

//       querySnapshot.forEach((client) => {
//         if (client.data().number === clientId) {

//           searchingClient = doc(db, "clients", client.id);

//         }
//       });



//       await updateDoc(searchingClient, {
//         domain: client.domain,
//         email: client.email,
//         key: client.key, //placement key 
//         hook: client.hook,  //hook url
//         clientId: client.clientId,
//         clientSecret: client.clientSecret
//       });

//       const resultClientsFetch = query(collection(db, "clients"), where("number", "==", clientId));
//       const resultClients = await getDocs(resultClientsFetch);


//       resultClients.forEach((doc) => {
//         if (doc.data().number === clientId) {
//           result = doc.data()
//         }
//       });

//       if (result && result.number) {

//         //APRIL ONLINE
//         console.log({
//           domain: result.domain,
//           key: result.key, //placement key 
//           clientId: result.clientId,
//           clientSecret: result.clientSecret,
//           hook: result.hook //hook url
//         })

//         let onlinePostPortal = await onlineAPI.setPortal(
//           result.number,
//           result.domain,
//           result.key, //placement key 
//           result.clientId,
//           result.clientSecret,
//           result.hook //hook url
//         )

//         console.log(onlinePostPortal)




//         return { resultCode: 0, client: result }
//       } else {
//         return { resultCode: 1, message: 'client is no updated' }
//       }

//     } catch (error) {

//       return { resultCode: 1, message: error.message && 'client is no updated' }

//     }
//   },

//   getClients: async () => {
//     let result = []
//     try {
//       const queryGet = query(collection(db, "clients"), orderBy("name"));
//       const querySnapshot = await getDocs(queryGet);


//       querySnapshot.forEach((doc) => {
//         let data = doc.data()
//         result.push(data)

//       });
//       console.log('get clients')
//       console.log(result)

//       return result
//     } catch (error) {
//       console.log(error)
//       return result

//     }


//   },

//   getClient: async (clientId) => {
//     let result = undefined
//     try {

//       const queryGet = query(collection(db, "clients"), where("number", "==", clientId));
//       const querySnapshot = await getDocs(queryGet);


//       querySnapshot.forEach((doc) => {
//         if (doc.data().number === clientId) {
//           result = doc.data()
//         }
//       });
//       return result
//     } catch (error) {
//       console.log(error)
//       return result

//     }


//   },

//   setProducts: async (clientId, products) => {
//     let result
//     try {

//       const queryGet = query(collection(db, "clients"), where("number", "==", clientId));
//       const querySnapshot = await getDocs(queryGet);


//       let searchingClient

//       querySnapshot.forEach((client) => {
//         if (client.data().number === clientId) {
//           // result = client.data()
//           searchingClient = doc(db, "clients", client.id);

//         }
//       });


//       await updateDoc(searchingClient, { products });

//       const resultClientsFetch = query(collection(db, "clients"), where("number", "==", clientId));
//       const resultClients = await getDocs(resultClientsFetch);


//       resultClients.forEach((doc) => {
//         if (doc.data().number === clientId) {
//           result = doc.data()
//         }
//       });
//       return result
//     } catch (error) {
//       console.log(error)
//       return result

//     }
//   },

//   updateFields: async (fields, clientId) => {



//     let result
//     try {

//       const queryGet = query(collection(db, "clients"), where("number", "==", clientId));
//       const querySnapshot = await getDocs(queryGet);


//       let searchingClient

//       querySnapshot.forEach((client) => {
//         if (client.data().number === clientId) {
//           // result = client.data()
//           searchingClient = doc(db, "clients", client.id);

//         }
//       });


//       await updateDoc(searchingClient, { fields });

//       const resultClientsFetch = query(collection(db, "clients"), where("number", "==", clientId));
//       const resultClients = await getDocs(resultClientsFetch);


//       resultClients.forEach((doc) => {
//         if (doc.data().number === clientId) {
//           result = doc.data()
//         }
//       });
//       return result
//     } catch (error) {
//       console.log(error)
//       return result

//     }
//   },

//   updateClientsProducts: async (newProductData) => {

//     // Получаем все документы из коллекции "clients"
//     const queryGet = query(collection(db, "clients"), orderBy("name"));
//     const clientsSnapshot = await getDocs(queryGet);

//     // Перебираем каждый документ (клиента) в коллекции



//     clientsSnapshot.docs.forEach(async (clientDoc) => {
//       // Получаем ссылку на коллекцию продуктов текущего клиента
//       const productsRef = collection(doc(db, 'clients', clientDoc.id), 'products');
//       const productsSnapshot = await getDocs(productsRef);

//       // Перебираем каждый продукт и обновляем его данные
//       productsSnapshot.docs.forEach(async (productDoc) => {
//         await setDoc(doc(productsRef, productDoc.id), newProductData, { merge: true });
//       });
//     });



//     const updatequeryGet = query(collection(db, "clients"), orderBy("name"));
//     const updateclientsSnapshot = await getDocs(updatequeryGet);

//     // Перебираем каждый документ (клиента) в коллекции
//     let result = []
//     updateclientsSnapshot.forEach((doc) => {
//       let data = doc.data()
//       result.push(data)

//     });


//     return result

//   },

//   getProducts: async (clientId) => {
//     let client = null
//     let id = null
//     let products = []

//     try {
//       const clientsQuery = query(collection(db, "clients"), where("number", "==", clientId));
//       const clientsQueryuerySnapshot = await getDocs(clientsQuery);


//       clientsQueryuerySnapshot.forEach((doc) => {
//         if (doc.data().number === clientId) {
//           client = doc.data()
//           id = doc.id
//         }
//       });


//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "ClientProducts"), where("clientId", "==", id));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         products.push(data)
//       });

//       return products
//     } catch (error) {

//       console.log(error)
//     }

//     return products
//   },

//   setRegions: async (regions, clientId) => {
//     let result = undefined
//     try {

//       const queryGet = query(collection(db, "clients"), where("number", "==", clientId));
//       const querySnapshot = await getDocs(queryGet);


//       querySnapshot.forEach((doc) => {
//         if (doc.data().number === clientId) {
//           result = doc.data()
//         }
//       });
//       return result
//     } catch (error) {
//       console.log(error)
//       return result

//     }

//   },

//   updateClientsContracts: async (newContracts) => {
//     let result = null
//     // Получаем все документы из коллекции "clients"
//     const queryGet = query(collection(db, "clients"), orderBy("name"));
//     const clientsSnapshot = await getDocs(queryGet);

//     // Перебираем каждый документ (клиента) в коллекции



//     clientsSnapshot.docs.forEach(async (clientDoc) => {

//       // Получаем ссылку на коллекцию продуктов текущего клиента
//       let clientData = clientDoc.data();
//       let clientContracts = clientData.contracts.items;

//       // Обновляем контракты если пришли такие же как уже есть: 
//       // Перебираем каждый контракт клиента и обновляем его данные
//       let updatedContracts = clientContracts.map((clientContract) => {
//         let newContract = newContracts.find(c => c.number === clientContract.number);
//         if (newContract) {
//           return {
//             ...newContract,
//             itemId: clientContract.itemId,
//             measureId: clientContract.measureId,
//             measureCode: clientContract.measureCode,
//           }
//         }
//         // Если нет нового контракта, возвращаем старый без изменений
//         return clientContract;
//       });
//       //перебираем новые контракты
//       newContracts.forEach(c => {
//         //если такого контракта у клиента нет
//         let isHaveContract = updatedContracts.find(uc => uc.number == c.number)
//         console.log('isHaveContract')
//         console.log(isHaveContract)

//         if (!isHaveContract) {
//           //пушим контракт к клиенту 
//           updatedContracts.push(c)
//         }
//       })
//       // Обновляем поле contracts.items у клиента
//       await updateDoc(doc(db, 'clients', clientDoc.id), { 'contracts.items': updatedContracts });


//     });



//     const resultClientsQueryGet = query(collection(db, "clients"), orderBy("name"));
//     const resultClientsQuerySnapshot = await getDocs(resultClientsQueryGet);

//     result = []
//     resultClientsQuerySnapshot.forEach((doc) => {
//       let client = doc.data()

//       result.push({
//         clientDomain: client.domain,
//         newContracts: client.contracts,
//       })

//     });

//     console.log(result)
//     console.log('result')
//     return result

//   },

//   clientFieldsGenerate: async () => {


//     // const testFunction = httpsCallable(functions, 'clientFieldsGenerate');
//     try {

//       const test = await generalAPI.clientFieldGenerate()

//       console.log(test)
//     } catch (error) {
//       console.log(error)

//     }


//   }
// }

// export const generalAPI = {

//   getDocByProp: async (collectionName, propName, propValue) => {
//     let result = null
//     try {
//       const queryGet = query(collection(db, collectionName), where(propName, "==", propValue));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         result = doc.data()
//       });

//       return result
//     } catch (error) {

//       return result
//     }
//   },

//   getDoc: async (collectionName, number) => {

//     let result = null
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, collectionName), where("number", "==", number));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         result = doc.data()
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//       return result

//     }


//   },

//   getCollection: async (collectionName) => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, collectionName), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },

//   setCollection: async (collectionName, objects) => {

//     try {


//       let docRef = null




//       const chunks = makeChunks(objects, 500);
//       for (let i = 0; i < chunks.length; i++) {

//         const batch = writeBatch(db)
//         chunks[i].forEach((element) => {
//           let number = element.number ? `${element.number}` : null
//           docRef = docRef = doc(db, collectionName, `${element.number}`);
//           batch.set(docRef, element, `${element.number}`)

//         });

//         await batch.commit();
//       }
//       let result = await generalAPI.getCollection(collectionName)

//       return result


//     } catch (error) {

//       console.error(error)
//     }
//   },

//   setCollectionUniqueId: async (collectionName, objects) => {

//     try {


//       let docRef = null
//       const chunks = makeChunks(objects, 500);
//       for (let i = 0; i < chunks.length; i++) {

//         const batch = writeBatch(db)
//         chunks[i].forEach((element) => {
//           // Получите ссылку на коллекцию
//           const collectionRef = collection(db, collectionName);
//           // Создайте новую ссылку на документ в этой коллекции без указания ID
//           const docRef = doc(collectionRef);
//           // Используйте эту ссылку в пакетной записи
//           batch.set(docRef, element);

//         });
//         await batch.commit();
//       }
//       let result = await generalAPI.getCollection(collectionName)

//       return result


//     } catch (error) {

//       console.error(error)
//     }
//   },

//   updateProp: async (collectionName, docId, newProp, propName) => {



//     let result
//     try {

//       const queryGet = query(collection(db, collectionName), where("number", "==", docId));
//       const querySnapshot = await getDocs(queryGet);


//       let searchingClient

//       querySnapshot.forEach((client) => {
//         if (client.data().number === docId) {
//           // result = client.data()
//           searchingClient = doc(db, collectionName, client.id);

//         }
//       });


//       await updateDoc(searchingClient, { [`${propName}`]: newProp });

//       const resultClientsFetch = query(collection(db, collectionName), where("number", "==", docId));
//       const resultDocs = await getDocs(resultClientsFetch);


//       resultDocs.forEach((doc) => {
//         if (doc.data().number === docId) {
//           result = doc.data()
//         }
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//       return result

//     }
//   },

//   updatetCollection: async (collectionName, objects) => {

//     try {


//       let docRef = null




//       const chunks = makeChunks(objects, 500);
//       for (let i = 0; i < chunks.length; i++) {

//         const batch = writeBatch(db)
//         chunks[i].forEach((element) => {
//           docRef = docRef = doc(db, collectionName, `${element.number}`);
//           batch.set(docRef, element, `${element.number}`)
//         });
//         await batch.commit();
//       }

//       let result = await generalAPI.getCollection(collectionName)

//       return result


//     } catch (error) {

//       console.error(error)
//     }
//   },

//   updateFrontend: async (isProd) => {
//     try {
//       let response = api.get(`${isProd}`)
//       if (response) {
//         return response
//       }
//     } catch (error) {
//       console.log(error)
//     }

//   },

//   clientFieldGenerate: async () => {
//     let clients = await generalAPI.getCollection('clients')
//     let fields = await generalAPI.getCollection('fields')
//     let clientFields = []


//     clients.forEach((client) => {



//       fields.forEach((field) => {

//         let isEditable = field.isEditableBitrix == true || field.isEditableValue == true
//         let tempClientField = client.fields.find(f => f.number === field.number)

//         let customBirixId = tempClientField ? tempClientField.bitrixId : field.bitrixId
//         let customValue = tempClientField ? tempClientField.value : field.value
//         let number = `${client.number}.${field.number}`

//         if (isEditable) {
//           let clientField = {
//             number,
//             fieldNumber: field.number,
//             clientNumber: client.number,
//             properties: {
//               bitrixId: customBirixId,
//               value: customValue
//             },
//             currentCustomProperties: [
//               'bitrixId', 'value'
//             ]


//           }
//           clientFields.push(clientField);



//         }



//       })



//     })


//     let result = await generalAPI.setCollectionUniqueId('clientFields', clientFields)

//     return result

//   },
// }




// // export const dealAPI = {

// //   addDeal: async (dealId, domain, state) => {
// //     // За кулисами .add(...) и .doc().set(...) полностью эквивалентны, поэтому вы можете использовать то, что удобнее
// //     //with ID
// //     await setDoc(doc(db, "cities", "new-city-id"), data);


// //     //or

// //     //without ID
// //     // Add a new document with a generated id.


// //     const docRef = await addDoc(collection(db, "deals"), {
// //       dealId, domain, state
// //     });
// //     console.log("Document written with ID: ", docRef.id);


// //   },

// //   updateDeal: async () => {
// //     const washingtonRef = doc(db, "cities", "DC");

// //     // Set the "capital" field of the city 'DC'
// //     await updateDoc(washingtonRef, {
// //       capital: true
// //     });
// //   },

// //   // getDefaultSettings



// //   deleteDeal: async () => {
// //     await deleteDoc(doc(db, "cities", "DC"));
// //   },
// // }







// export const fieldsAPI = {

//   getFields: async () => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "fields"), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },

//   setFields: async (fields) => {
//     try {

//       const batch = writeBatch(db)
//       let field = {}

//       let docRef = null
//       // for (const key in fields) {

//       for (let i = 0; i < fields.length; i++) {


//         console.log(fields[i].id)
//         field = fields[i]

//         docRef = doc(db, "fields", `${fields[i].id}`);
//         let newDoc = batch.set(docRef, field, `${fields[i].id}`)
//         console.log(newDoc)


//       }



//       // }


//       //  let newDoc = await setDoc(docRef, field, 1);

//       await batch.commit();




//     } catch (error) {
//       console.error(error)
//     }
//   },

//   updateField: async (fieldNumber, value, type) => {


//     let docRef = doc(db, "fields", `${fieldNumber}`)

//     // const docSnap = await getDoc(docRef);
//     // if (docSnap.exists()) {
//     // let field = docSnap.data()

//     // }
//     await setDoc(docRef, { [`${type}`]: value }, { merge: true });

//     let result = []
//     let updatedField = await getDoc(docRef)

//     if (updatedField.exists()) {
//       console.log(updatedField.data())
//       result = updatedField.data()
//       console.log("Document data:", updatedField.data());

//     }
//     return result
//   }


// }

// export const complectsAPI = {

//   setComplects: async (complects) => {
//     try {

//       const batch = writeBatch(db)
//       // let field = {}

//       let docRef = null
//       // for (const key in fields) {

//       for (let i = 0; i < complects.length; i++) {


//         console.log(complects[i].number)
//         const complect = complects[i]

//         docRef = doc(db, "complects", `${complects[i].number}`);
//         let newDoc = batch.set(docRef, complect, `${complects[i].number}`)
//         console.log(newDoc)


//       }

//       await batch.commit();
//       let result = await complectsAPI.getComplects()



//       return result

//       // }


//       //  let newDoc = await setDoc(docRef, field, 1);






//     } catch (error) {
//       console.error(error)
//     }
//   },

//   getComplects: async () => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "complects"), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },
// }

// export const supplyAPI = {

//   setSupplies: async (supplies) => {
//     try {

//       const batch = writeBatch(db)
//       // let field = {}

//       let docRef = null
//       // for (const key in fields) {

//       for (let i = 0; i < supplies.length; i++) {


//         console.log(supplies[i].number)
//         const supply = supplies[i]

//         docRef = doc(db, "supplies", `${supplies[i].number}`);
//         let newDoc = batch.set(docRef, supply, `${supplies[i].number}`)
//         console.log(newDoc)


//       }

//       await batch.commit();
//       let result = await supplyAPI.getSupplies()



//       return result


//     } catch (error) {
//       console.error(error)
//     }
//   },

//   getSupplies: async () => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "supplies"), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },
// }

// export const regionAPI = {

//   setRegions: async (regions) => {
//     try {

//       const batch = writeBatch(db)
//       // let field = {}

//       let docRef = null
//       // for (const key in fields) {

//       for (let i = 0; i < regions.length; i++) {


//         console.log(regions[i].number)
//         const region = regions[i]

//         docRef = doc(db, "regions", `${regions[i].number}`);
//         let newDoc = batch.set(docRef, region, `${regions[i].number}`)
//         console.log(newDoc)


//       }

//       await batch.commit();
//       let result = await regionAPI.getRegions()



//       return result


//     } catch (error) {
//       console.error(error)
//     }
//   },

//   getRegions: async () => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "regions"), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },
// }

// export const consaltingAPI = {

//   setConsalting: async (consalting) => {
//     try {

//       const batch = writeBatch(db)
//       // let field = {}

//       let docRef = null
//       // for (const key in fields) {

//       for (let i = 0; i < consalting.length; i++) {


//         console.log(consalting[i].number)
//         const consaltingItem = consalting[i]

//         docRef = doc(db, "consalting", `${consalting[i].number}`);
//         let newDoc = batch.set(docRef, consaltingItem, `${consalting[i].number}`)
//         console.log(newDoc)


//       }

//       await batch.commit();
//       let result = await consaltingAPI.getConsalting()



//       return result


//     } catch (error) {
//       console.error(error)
//     }
//   },

//   getConsalting: async () => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "consalting"), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },
// }


// export const contractAPI = {

//   setContracts: async (contracts) => {
//     try {

//       const batch = writeBatch(db)
//       // let field = {}

//       let docRef = null
//       // for (const key in fields) {

//       for (let i = 0; i < contracts.length; i++) {


//         console.log(contracts[i].number)
//         const contract = contracts[i]

//         docRef = doc(db, "contracts", `${contracts[i].number}`);
//         let newDoc = batch.set(docRef, contract, `${contracts[i].number}`)
//         console.log(newDoc)


//       }

//       await batch.commit();
//       let result = await contractAPI.getContracts()



//       return result


//     } catch (error) {
//       console.error(error)
//     }
//   },

//   getContracts: async () => {

//     let result = []
//     try {
//       // const querySnapshot = await getDocs(collection(db, "fields"), orderBy("number"));
//       const queryGet = query(collection(db, "contracts"), orderBy("number"));
//       const querySnapshot = await getDocs(queryGet);

//       querySnapshot.forEach((doc) => {
//         let data = doc.data()

//         result.push(data)
//       });

//       return result
//     } catch (error) {

//       console.log(error)
//     }

//     return result
//   },
// }



// export const ltAPI = {

//   updatePrices: async (prices) => {
//     let lt = []
//     let result = undefined
//     try {
//       const queryGet = query(collection(db, "legalTech"));
//       const querySnapshot = await getDocs(queryGet);


//       querySnapshot.forEach((doc) => {
//         let data = doc.data()
//         lt.push(data)

//       });
//       lt = lt.map(lt => {
//         if (lt.type === 'package') {

//           prices.forEach(price => {
//             if (price.complectNumber === lt.number) {
//               if (price.region === 1) {//price=msk
//                 lt.msk = price.price
//               } else if (price.region === 0) { //rgns
//                 lt.regions = price.price
//               }
//             }
//           })

//         }
//         return lt
//       })
//       result = await generalAPI.setCollection('legalTech', lt)
//       return result
//     } catch (error) {
//       console.log(error)
//       return result
//     }
//   },

//   updateClient: async (clientId, fields) => {
//     const docRef = doc(db, "clients", clientId);
//     const docSnap = await getDoc(docRef);

//     let client = null
//     if (docSnap.exists()) {
//       client = docSnap.data()

//       console.log('client');
//       console.log(client);
//     } else {
//       console.log("No such document!");
//     }
//   },

//   getClients: async () => {
//     let result = []
//     try {
//       const queryGet = query(collection(db, "clients"), orderBy("name"));
//       const querySnapshot = await getDocs(queryGet);


//       querySnapshot.forEach((doc) => {
//         let data = doc.data()
//         result.push(data)

//       });
//       console.log('get clients')
//       console.log(result)

//       return result
//     } catch (error) {
//       console.log(error)
//       return result

//     }


//   },
// }





// export const authApi = {
//   getAuth: async () => {

//     const provider = new GoogleAuthProvider();
//     const auth = getAuth(app);

//     auth.languageCode = 'ru';

//     await signInWithPopup(auth, provider)
//       .then((result) => {
//         // This gives you a Google Access Token. You can use it to access the Google API.
//         // const credential = GoogleAuthProvider.credentialFromResult(result);
//         // const token = credential.accessToken;
//         // The signed-in user info.
//         const user = result.user;
//         // resultUser = user.name
//         // IdP data available using getAdditionalUserInfo(result)
//         // ...




//         console.log(user)

//         return user

//       }).catch((error) => {

//         // Handle Errors here.
//         // const errorCode = error.code;
//         const errorMessage = error.message;
//         // The email of the user's account used.
//         // const email = error.customData.email;
//         // The AuthCredential type that was used.
//         const credential = GoogleAuthProvider.credentialFromError(error);
//         console.log(errorMessage)
//         console.log(credential)

//       });
//     const user = auth.currentUser;


//     //todo login with google data?

//     return user

//   },

//   getCurrentUser: async () => {
//     const auth = getAuth();
//     let resultUser = null
//     onAuthStateChanged(auth, (user) => {
//       if (user) {
//         // User is signed in, see docs for a list of available properties
//         // https://firebase.google.com/docs/reference/js/auth.user
//         // const uid = user.uid;
//         resultUser = user

//         // ...
//       } else {
//         // User is signed out
//         // ...
//       }

//     });

//     return resultUser
//   },

//   async login(email, password) {

//     const auth = getAuth();
//     signInWithEmailAndPassword(auth, email, password)
//       .then((userCredential) => {
//         // Signed in 
//         const user = userCredential.user;
//         console.log(user)
//       })
//       .catch((error) => {
//         const errorCode = error.code;
//         const errorMessage = error.message;
//         console.log(error.message)
//       });

//   },

//   async getProfile(email, password) {

//     const auth = getAuth();
//     signInWithEmailAndPassword(auth, email, password)
//       .then((userCredential) => {
//         // Signed in 
//         const user = userCredential.user;
//         console.log(user)
//       })
//       .catch((error) => {
//         const errorCode = error.code;
//         const errorMessage = error.message;
//         console.log(error.message)
//       });

//   },
// }
