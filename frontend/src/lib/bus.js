import mitt from 'mitt';

const emitter = mitt();

const bus = {
    all: emitter.all,
    emit: (type, event) => emitter.emit(type, event),
    off: (type, handler) => emitter.off(type, handler),
    on: (type, handler) => {
        emitter.on(type, handler);
        return () => emitter.off(type, handler);
    }
};

export default bus;
