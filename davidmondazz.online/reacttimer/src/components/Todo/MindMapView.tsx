import React, { useEffect, useRef, useState } from 'react';
import {
  Box,
  Circle,
  Flex,
  Text,
  useColorModeValue,
  Badge,
  HStack,
  Icon,
  VStack,
  Tooltip,
  useBreakpointValue,
} from '@chakra-ui/react';
import { FaCheck, FaCoins, FaClock, FaLink } from 'react-icons/fa';
import { TodoItem } from '../../types';
import { TODO_BASE_RATE, calculateReward } from './TodoLevelInfoModal';

interface MindMapViewProps {
  todos: TodoItem[];
  onEdit: (todo: TodoItem) => void;
  onToggleComplete: (todo: TodoItem) => void;
  globalLevel?: number;
  globalReward?: number;
}

// Define a node type for the tree structure
interface TodoNode {
  todo: TodoItem;
  children: TodoNode[];
  x?: number;
  y?: number;
  angle?: number;
  radius?: number;
}

const MindMapView: React.FC<MindMapViewProps> = ({ 
  todos, 
  onEdit, 
  onToggleComplete,
  globalLevel = 1,
  globalReward = TODO_BASE_RATE
}) => {
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const completedBgColor = useColorModeValue('gray.50', 'gray.700');
  const lineBgColor = useColorModeValue('gray.300', 'gray.600');
  const mainNodeColor = useColorModeValue('blue.500', 'blue.300');
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const [treeData, setTreeData] = useState<TodoNode | null>(null);
  const [containerSize, setContainerSize] = useState({ width: 800, height: 600 });
  const [lines, setLines] = useState<{from: {x: number, y: number}, to: {x: number, y: number}, color: string}[]>([]);
  
  // Set of theme colors for branches
  const branchColors = [
    { bg: 'blue.400', text: 'white' },
    { bg: 'green.400', text: 'white' },
    { bg: 'purple.400', text: 'white' },
    { bg: 'orange.400', text: 'white' },
    { bg: 'pink.400', text: 'white' },
    { bg: 'cyan.400', text: 'white' },
    { bg: 'red.400', text: 'white' },
  ];
  
  // Calculate node sizes based on viewport
  const nodeSizes = {
    center: useBreakpointValue({ base: 120, md: 150, lg: 180 }) || 150,
    primary: useBreakpointValue({ base: 90, md: 110, lg: 130 }) || 110,
    secondary: useBreakpointValue({ base: 70, md: 80, lg: 90 }) || 80,
    tertiary: useBreakpointValue({ base: 50, md: 60, lg: 70 }) || 60,
  };
  
  // Helper function to build tree structure
  const buildTree = (todos: TodoItem[]): TodoNode | null => {
    if (todos.length === 0) return null;

    // Create a map of todos by ID for quick lookup
    const todoMap = new Map<string, TodoItem>();
    todos.forEach(todo => todoMap.set(todo.id, todo));
    
    // Create a map to track nodes
    const nodeMap = new Map<string, TodoNode>();
    
    // Find root todos (those without a parent or with invalid parent)
    const rootTodos = todos.filter(todo => 
      !todo.parentId || !todoMap.has(todo.parentId)
    );
    
    // If no root todos, use the first todo as root
    if (rootTodos.length === 0 && todos.length > 0) {
      const rootTodo = todos[0];
      const rootNode: TodoNode = { todo: rootTodo, children: [] };
      nodeMap.set(rootTodo.id, rootNode);
      
      // All other todos will be direct children
      const remainingTodos = todos.slice(1);
      remainingTodos.forEach(todo => {
        const node: TodoNode = { todo, children: [] };
        nodeMap.set(todo.id, node);
        rootNode.children.push(node);
      });
      
      return rootNode;
    }
    
    // Create a virtual root node with all root todos as children
    const virtualRoot: TodoNode = {
      todo: {
        id: 'virtual-root',
        title: `Tasks (Level ${globalLevel})`,
        description: 'All Tasks',
        completed: false,
        priority: 'medium',
        reward: 0,
        createdAt: Date.now(),
        updatedAt: Date.now(),
        position: 0,
        level: 0,
      },
      children: [],
    };
    
    // Process each root todo
    rootTodos.forEach(rootTodo => {
      const rootNode: TodoNode = { todo: rootTodo, children: [] };
      nodeMap.set(rootTodo.id, rootNode);
      virtualRoot.children.push(rootNode);
    });
    
    // Process non-root todos
    todos
      .filter(todo => todo.parentId && todoMap.has(todo.parentId))
      .forEach(todo => {
        const node: TodoNode = { todo, children: [] };
        nodeMap.set(todo.id, node);
        
        // Add to parent's children
        const parentNode = nodeMap.get(todo.parentId!);
        if (parentNode) {
          parentNode.children.push(node);
        }
      });
    
    // Sort children by position
    const sortChildren = (node: TodoNode) => {
      node.children.sort((a, b) => a.todo.position - b.todo.position);
      node.children.forEach(sortChildren);
    };
    
    sortChildren(virtualRoot);
    
    return virtualRoot;
  };
  
  // Helper function to position nodes in a radial layout
  const layoutTree = (root: TodoNode, centerX: number, centerY: number, radius: number = 250) => {
    // Position the root at the center
    root.x = centerX;
    root.y = centerY;
    
    // Helper function to recursively layout the tree
    const layoutNode = (node: TodoNode, level: number, startAngle: number, endAngle: number) => {
      const children = node.children;
      if (children.length === 0) return;
      
      // Calculate the angular span per child
      const angleSpan = (endAngle - startAngle) / children.length;
      
      // Layout each child
      children.forEach((child, index) => {
        // Calculate the angle for this child
        const childAngle = startAngle + (index * angleSpan) + (angleSpan / 2);
        
        // Calculate the radius for this level
        const childRadius = level === 0 ? radius : radius / (1 + (level * 0.5));
        
        // Calculate the position
        child.x = node.x! + (childRadius * Math.cos(childAngle));
        child.y = node.y! + (childRadius * Math.sin(childAngle));
        child.angle = childAngle;
        child.radius = childRadius;
        
        // Recursively layout this child's children
        layoutNode(child, level + 1, childAngle - (angleSpan / 2), childAngle + (angleSpan / 2));
      });
    };
    
    // Start the layout with the root's children
    layoutNode(root, 0, 0, 2 * Math.PI);
    
    return root;
  };
  
  // Generate lines to connect nodes
  const generateLines = (root: TodoNode, colorMap: Map<string, string>) => {
    const lines: {from: {x: number, y: number}, to: {x: number, y: number}, color: string}[] = [];
    
    const processNode = (node: TodoNode) => {
      const fromX = node.x || 0;
      const fromY = node.y || 0;
      
      node.children.forEach(child => {
        const toX = child.x || 0;
        const toY = child.y || 0;
        
        // Determine the line color based on the branch
        const color = colorMap.get(child.todo.id) || 'gray';
        
        lines.push({
          from: { x: fromX, y: fromY },
          to: { x: toX, y: toY },
          color
        });
        
        // Process this child's connections
        processNode(child);
      });
    };
    
    processNode(root);
    return lines;
  };
  
  // Helper function to assign colors to branches
  const assignBranchColors = (root: TodoNode) => {
    const colorMap = new Map<string, string>();
    
    // Assign colors to primary branches
    root.children.forEach((child, index) => {
      const colorIndex = index % branchColors.length;
      const color = branchColors[colorIndex].bg;
      
      // Assign to this node and all its descendants
      const assignColor = (node: TodoNode, color: string) => {
        colorMap.set(node.todo.id, color);
        node.children.forEach(child => assignColor(child, color));
      };
      
      assignColor(child, color);
    });
    
    return colorMap;
  };
  
  const formatDate = (timestamp?: number | null) => {
    if (!timestamp) return 'No due date';
    return new Date(timestamp).toLocaleDateString();
  };
  
  // Render a node based on its level in the tree
  const renderNode = (node: TodoNode, level: number, color: string) => {
    const { todo } = node;
    const isRoot = level === 0;
    const isPrimary = level === 1;
    const isSecondary = level === 2;
    const isTertiary = level >= 3;
    
    // Determine size based on level
    let size = nodeSizes.tertiary;
    if (isRoot) size = nodeSizes.center;
    else if (isPrimary) size = nodeSizes.primary;
    else if (isSecondary) size = nodeSizes.secondary;
    
    // Calculate the font size based on the node size
    const fontSize = size * 0.14;
    
    // For the root node (virtual root)
    if (isRoot) {
      return (
        <Circle
          key={todo.id}
          size={`${size}px`}
          bg={mainNodeColor}
          color="white"
          fontWeight="bold"
          boxShadow="md"
          position="absolute"
          left={`${node.x}px`}
          top={`${node.y}px`}
          transform="translate(-50%, -50%)"
          zIndex={10}
          fontSize={`${fontSize}px`}
        >
          <VStack spacing={0}>
            <Text>{todo.title}</Text>
            <Badge colorScheme="yellow" fontSize="xs">
              <HStack spacing={1}>
                <Icon as={FaCoins} boxSize="0.6em" />
                <Text>${globalReward.toFixed(3)}/task</Text>
              </HStack>
            </Badge>
          </VStack>
        </Circle>
      );
    }
    
    // For regular nodes
    return (
      <Tooltip
        key={todo.id}
        label={
          <VStack align="start" spacing={1}>
            <Text fontWeight="bold">{todo.title}</Text>
            {todo.description && <Text fontSize="sm">{todo.description}</Text>}
            <HStack>
              <Text fontSize="xs">Priority: {todo.priority}</Text>
            </HStack>
            <HStack>
              <Text fontSize="xs">Reward: ${globalReward.toFixed(4)}</Text>
            </HStack>
          </VStack>
        }
        placement="top"
        hasArrow
      >
        <Box
          position="absolute"
          left={`${node.x}px`}
          top={`${node.y}px`}
          transform="translate(-50%, -50%)"
          zIndex={5}
          onClick={() => onEdit(todo)}
          cursor="pointer"
        >
          <Circle
            size={`${size}px`}
            bg={todo.completed ? completedBgColor : color}
            color={isPrimary ? "white" : "black"}
            fontWeight="medium"
            boxShadow="sm"
            borderWidth="1px"
            borderColor={borderColor}
            transition="all 0.2s"
            opacity={todo.completed ? 0.7 : 1}
            _hover={{ 
              boxShadow: 'md',
              transform: 'scale(1.05)'
            }}
            overflow="hidden"
            position="relative"
          >
            <Box position="absolute" inset="0" p={2}>
              <VStack spacing={0} height="100%" justify="center">
                <Text
                  fontSize={`${fontSize}px`}
                  fontWeight="bold"
                  textAlign="center"
                  isTruncated
                  textDecoration={todo.completed ? 'line-through' : 'none'}
                >
                  {todo.title}
                </Text>
                
                {(isPrimary || isSecondary) && (
                  <HStack spacing={1} mt={1} justify="center">
                    <Circle 
                      size={`${size * 0.2}px`}
                      bg={todo.completed ? 'green.500' : 'transparent'}
                      borderWidth="1px"
                      borderColor={todo.completed ? 'green.500' : 'gray.400'}
                      onClick={(e) => {
                        e.stopPropagation();
                        onToggleComplete(todo);
                      }}
                    >
                      {todo.completed && <Icon as={FaCheck} fontSize={`${size * 0.1}px`} color="white" />}
                    </Circle>
                  </HStack>
                )}
                
                {isPrimary && (
                  <Badge colorScheme="yellow" fontSize="xs" mt={1}>
                    <HStack spacing={1} alignItems="center">
                      <Icon as={FaCoins} boxSize="0.6em" />
                      <Text fontSize="xs">${globalReward.toFixed(3)}</Text>
                    </HStack>
                  </Badge>
                )}
              </VStack>
            </Box>
          </Circle>
        </Box>
      </Tooltip>
    );
  };
  
  // Effect to update container size on window resize
  useEffect(() => {
    const updateSize = () => {
      if (mapContainerRef.current) {
        const { width, height } = mapContainerRef.current.getBoundingClientRect();
        setContainerSize({ width, height });
      }
    };
    
    // Initial size update
    updateSize();
    
    // Add resize listener
    window.addEventListener('resize', updateSize);
    
    // Cleanup
    return () => window.removeEventListener('resize', updateSize);
  }, []);
  
  // Effect to build and layout the tree when todos change
  useEffect(() => {
    if (todos.length === 0) {
      setTreeData(null);
      return;
    }
    
    // Build the tree structure
    const root = buildTree(todos);
    if (!root) return;
    
    // Determine the center of the container
    const centerX = containerSize.width / 2;
    const centerY = containerSize.height / 2;
    
    // Layout the tree
    const layoutedTree = layoutTree(root, centerX, centerY, Math.min(centerX, centerY) * 0.7);
    
    // Assign colors to branches
    const colorMap = assignBranchColors(root);
    
    // Generate connection lines
    const connectionLines = generateLines(layoutedTree, colorMap);
    
    setTreeData(layoutedTree);
    setLines(connectionLines);
  }, [todos, containerSize, globalLevel]);
  
  if (todos.length === 0) {
    return (
      <Box p={4} textAlign="center">
        <Text>No tasks found. Create a new task to get started!</Text>
      </Box>
    );
  }
  
  return (
    <Box 
      ref={mapContainerRef} 
      position="relative" 
      height="800px"
      border="1px solid"
      borderColor={borderColor}
      borderRadius="lg"
      overflow="hidden"
      bg={bgColor}
    >
      {/* SVG Layer for connection lines */}
      <svg
        width="100%"
        height="100%"
        style={{
          position: 'absolute',
          top: 0,
          left: 0,
          zIndex: 1,
        }}
      >
        {lines.map((line, index) => (
          <line
            key={index}
            x1={line.from.x}
            y1={line.from.y}
            x2={line.to.x}
            y2={line.to.y}
            stroke={line.color}
            strokeWidth="2"
          />
        ))}
      </svg>
      
      {/* Node Layer */}
      {treeData && (
        <>
          {/* Render the root */}
          {renderNode(treeData, 0, mainNodeColor)}
          
          {/* Render all children recursively */}
          {treeData.children.map((child, index) => {
            const colorIndex = index % branchColors.length;
            const branchColor = branchColors[colorIndex].bg;
            
            // Helper function to recursively render nodes
            const renderChildren = (node: TodoNode, level: number) => {
              return (
                <React.Fragment key={node.todo.id}>
                  {renderNode(node, level, branchColor)}
                  {node.children.map(childNode => renderChildren(childNode, level + 1))}
                </React.Fragment>
              );
            };
            
            return renderChildren(child, 1);
          })}
        </>
      )}
    </Box>
  );
};

export default MindMapView; 